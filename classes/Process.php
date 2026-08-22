<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "includes/autoloader.php";

class Process extends Database
{

    public function saveProduct($data)
    {
        $conn = $this->connect();

        // Get the admin ID from the product data or the current user session
        $admin_id = 0;
        if (isset($data->admin_id) && intval($data->admin_id) > 0) {
            $admin_id = intval($data->admin_id);
        } elseif (isset($data['admin_id']) && intval($data['admin_id']) > 0) {
            $admin_id = intval($data['admin_id']);
        } elseif (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) > 0) {
            $admin_id = intval($_SESSION['user_id']);
        }

        // A product must have a valid owner
        if ($admin_id === 0) {
            die("Error: User session not found. Cannot save product without a valid owner.");
        }

        // Sanitize the main text fields
        // Add the admin ID to the SKU to make it unique for each seller
        $raw_sku = trim($data->sku);
        $admin_id = isset($data->admin_id) ? intval($data->admin_id) : 0;

        $sku = mysqli_real_escape_string($conn, $admin_id . "/" . $raw_sku);

        $name = mysqli_real_escape_string($conn, $data->name);
        $productType = mysqli_real_escape_string($conn, $data->productType);
        $image = isset($data->image) ? mysqli_real_escape_string($conn, $data->image) : 'default-product.png';

        // Sanitize the product description, categories, and filter fields
        $description = isset($data->description) ? mysqli_real_escape_string($conn, $data->description) : '';
        $category = mysqli_real_escape_string($conn, $data->category);
        $subcategory = mysqli_real_escape_string($conn, $data->subcategory);

        $furniture_room = !empty($data->furniture_room) ? "'" . mysqli_real_escape_string($conn, $data->furniture_room) . "'" : "NULL";

        $brand = !empty($data->brand) ? "'" . mysqli_real_escape_string($conn, $data->brand) . "'" : "NULL";
        $color = !empty($data->color) ? "'" . mysqli_real_escape_string($conn, $data->color) . "'" : "NULL";
        $size_attr = !empty($data->size_attr) ? "'" . mysqli_real_escape_string($conn, $data->size_attr) . "'" : "NULL";
        $gender = !empty($data->gender) ? "'" . mysqli_real_escape_string($conn, $data->gender) . "'" : "'Unisex'";

        // Convert price and discount values to numbers
        $price = floatval($data->price);
        $discount = isset($data->discount) ? intval($data->discount) : 0;

        // Convert optional numeric product attributes to integers
        $size   = (!empty($data->size))   ? intval($data->size)   : "NULL";
        $weight = (!empty($data->weight)) ? intval($data->weight) : "NULL";
        $height = (!empty($data->height)) ? intval($data->height) : "NULL";
        $width  = (!empty($data->width))  ? intval($data->width)  : "NULL";
        $length = (!empty($data->length)) ? intval($data->length) : "NULL";

        // Build the product insert query
        $query = "INSERT INTO " . $this->tbname . " (
            `admin_id`, `sku`, `name`, `description`, `price`, `discount`, `image`, `category`, `subcategory`, `furniture_room`, `brand`, `color`, `size_attr`, `gender`, `size`, `weight`, `height`, `width`, `length`, `productType`
        ) VALUES (
            $admin_id, '$sku', '$name', '$description', $price, $discount, '$image', '$category', '$subcategory', $furniture_room, $brand, $color, $size_attr, $gender, $size, $weight, $height, $width, $length, '$productType'
        )";

        // Insert the product into the database
        if (mysqli_query($conn, $query)) {

            // Get the ID of the newly created product
            $new_product_id = mysqli_insert_id($conn); // Го преземаме точниот ID од првиот и единствен запис

            // Save product sizes and quantities in the stock table
            if (isset($data->stockData) && is_array($data->stockData)) {
                foreach ($data->stockData as $stockItem) {
                    $item = (object)$stockItem;
                    $size_name = mysqli_real_escape_string($conn, $item->size);
                    $quantity = intval($item->qty);

                    // Save only stock entries with a quantity greater than zero
                    if ($quantity > 0) {
                        $stock_query = "INSERT INTO `product_stock` (`product_id`, `size_name`, `quantity`) VALUES ($new_product_id, '$size_name', $quantity)";
                        mysqli_query($conn, $stock_query);
                    }
                }
            }
        } else {
            // Show the database error if the insert fails
            die("Database Insert Error: " . mysqli_error($conn));
        }
    }



    public function getAllProducts($user_role, $user_id)
    {
        $conn = $this->connect(); // Use the core internal parent OOP connection pipeline

        $user_id = intval($user_id);
        $user_role = trim($user_role);

        // CLEAN PRODUCTION QUERY: Fetches pristine columns without forcing invalid dynamic key injections
        if ($user_role === 'admin') {
            // Sellers see their own active products inside the table matrix cleanly
            $query = "SELECT * FROM `producttable` WHERE admin_id = $user_id ORDER BY id DESC";
        } else {
            // Shoppers and guest visitor sessions fetch all registered items cleanly from the live database
            $query = "SELECT * FROM `producttable` ORDER BY id DESC";
        }

        $result = mysqli_query($conn, $query);
        $products = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Safely assign data structures by validating array keys to prevent runtime crash drops
                $row['total_qty'] = isset($row['total_qty']) ? intval($row['total_qty']) : 0;
                $row['stock_summary'] = isset($row['stock_summary']) ? $row['stock_summary'] : '';
                $products[] = $row;
            }
        }

        return $products;
    }


    public function delete($all_id)
    {
        $conn = $this->connect();

        // Convert all IDs to integers before using them in the query
        $clean_ids = array_map('intval', $all_id);
        $id_list = implode(',', $clean_ids);

        $result = mysqli_query($conn, "DELETE FROM $this->tbname WHERE `id` IN (" . $id_list . ")");
        return (bool)$result;
    }

    public function getCartProducts($ids_array)
    {
        $conn = $this->connect();
        $array = [];

        // Return an empty array if there are no product IDs
        if (empty($ids_array)) {
            return $array;
        }

        // Clean the product IDs before using them in the query
        $clean_ids = array_map('intval', $ids_array);
        $ids_string = implode(',', $clean_ids);

        // Get all products that match the given IDs
        $query = "SELECT * FROM " . $this->tbname . " WHERE id IN ($ids_string)";
        $result = mysqli_query($conn, $query);

        // Add the products to the result array
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $array[] = $row;
            }
        }
        return $array;
    }

    // Get all users with the seller role
    public function getAllSellers()
    {
        $conn = $this->connect();
        $array = [];
        $query = "SELECT `id`, `username`, `first_name`, `last_name`, `email`, `phone`, `address` FROM `users` WHERE `role` = 'admin' ORDER BY `id` DESC";
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $array[] = $row;
            }
        }
        return $array;
    }

    // Reset the password for a seller
    public function resetSellerPassword($userId, $newPlainPassword)
    {
        $conn = $this->connect();
        $userId = intval($userId);

        // Hash the new password before storing it
        $hashedPassword = password_hash($newPlainPassword, PASSWORD_DEFAULT);
        $query = "UPDATE `users` SET `password` = '$hashedPassword' WHERE `id` = $userId AND `role` = 'admin'";
        return mysqli_query($conn, $query);
    }

    // Delete a seller and their related products
    // Foreign key CASCADE rules will remove the related records automatically
    public function deleteSeller($userId)
    {
        $conn = $this->connect();
        $userId = intval($userId);
        $query = "DELETE FROM `users` WHERE `id` = $userId AND `role` = 'admin'";
        return mysqli_query($conn, $query);
    }
}
