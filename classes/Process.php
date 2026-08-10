<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "includes/autoloader.php";

class Process extends Database { 
    
    public function saveProduct($data){
        $conn = $this->connect();

        // Проверка на сопственикот (Admin ID)
        $admin_id = 0;
        if (isset($data->admin_id) && intval($data->admin_id) > 0) {
            $admin_id = intval($data->admin_id);
        } elseif (isset($data['admin_id']) && intval($data['admin_id']) > 0) {
            $admin_id = intval($data['admin_id']);
        } elseif (isset($_SESSION['user_id']) && intval($_SESSION['user_id']) > 0) {
            $admin_id = intval($_SESSION['user_id']);
        }

        if ($admin_id === 0) {
            die("Error: User session not found. Cannot save product without a valid owner.");
        }

        // 1. Санитација на стандардните текстуални низи
                // ПОПРАВЕНО: Автоматско генерирање на паметна SKU шифра во формат: [admin_id]/[sku]
        $raw_sku = trim($data->sku);
        $admin_id = isset($data->admin_id) ? intval($data->admin_id) : 0;
        
        $sku = mysqli_real_escape_string($conn, $admin_id . "/" . $raw_sku);

        $name = mysqli_real_escape_string($conn, $data->name);
        $productType = mysqli_real_escape_string($conn, $data->productType);
        $image = isset($data->image) ? mysqli_real_escape_string($conn, $data->image) : 'default-product.png';
        
        // 2. Санитација на новите е-комерц колони (ОПИС, КАТЕГОРИИ И ФИЛТРИ)
        $description = isset($data->description) ? mysqli_real_escape_string($conn, $data->description) : '';
        $category = mysqli_real_escape_string($conn, $data->category);
        $subcategory = mysqli_real_escape_string($conn, $data->subcategory);
        
        $furniture_room = !empty($data->furniture_room) ? "'" . mysqli_real_escape_string($conn, $data->furniture_room) . "'" : "NULL";

        $brand = !empty($data->brand) ? "'" . mysqli_real_escape_string($conn, $data->brand) . "'" : "NULL";
        $color = !empty($data->color) ? "'" . mysqli_real_escape_string($conn, $data->color) . "'" : "NULL";
        $size_attr = !empty($data->size_attr) ? "'" . mysqli_real_escape_string($conn, $data->size_attr) . "'" : "NULL";
        $gender = !empty($data->gender) ? "'" . mysqli_real_escape_string($conn, $data->gender) . "'" : "'Unisex'";

        // 3. Сигурно кастирање на бројките (Цена и Попуст)
        $price = floatval($data->price);
        $discount = isset($data->discount) ? intval($data->discount) : 0;

        // 4. Специфични атрибути во зависност од типот
        $size   = (!empty($data->size))   ? intval($data->size)   : "NULL";
        $weight = (!empty($data->weight)) ? intval($data->weight) : "NULL";
        $height = (!empty($data->height)) ? intval($data->height) : "NULL";
        $width  = (!empty($data->width))  ? intval($data->width)  : "NULL";
        $length = (!empty($data->length)) ? intval($data->length) : "NULL";

        // 5. Конструирање на упитот
        $query = "INSERT INTO " . $this->tbname . " (
            `admin_id`, `sku`, `name`, `description`, `price`, `discount`, `image`, `category`, `subcategory`, `furniture_room`, `brand`, `color`, `size_attr`, `gender`, `size`, `weight`, `height`, `width`, `length`, `productType`
        ) VALUES (
            $admin_id, '$sku', '$name', '$description', $price, $discount, '$image', '$category', '$subcategory', $furniture_room, $brand, $color, $size_attr, $gender, $size, $weight, $height, $width, $length, '$productType'
        )";

        // ПОПРАВЕНО: Упитот го извршуваме САМО ЕДНАШ внатре во условот за успех
        if (mysqli_query($conn, $query)) {
            $new_product_id = mysqli_insert_id($conn); // Го преземаме точниот ID од првиот и единствен запис
            
            // 6. Зачувување на големините и количините во product_stock
            if (isset($data->stockData) && is_array($data->stockData)) {
                foreach ($data->stockData as $stockItem) {
                    $item = (object)$stockItem;
                    $size_name = mysqli_real_escape_string($conn, $item->size);
                    $quantity = intval($item->qty);
                    
                    if ($quantity > 0) {
                        $stock_query = "INSERT INTO `product_stock` (`product_id`, `size_name`, `quantity`) VALUES ($new_product_id, '$size_name', $quantity)";
                        mysqli_query($conn, $stock_query);
                    }
                }
            }
        } else {
            // Доколку има било каква SQL грешка, ќе биде испишана тука при дебаг
            die("Database Insert Error: " . mysqli_error($conn));
        }
    }


    /**
     * Повлекување на сите производи со паметна OOP калкулација на инвентарот во реално време
     * @param string $user_role Улогата на корисникот од сесијата
     * @param int $user_id ИД-то на корисникот
     * @return array Низа од исчистени и подготвени производи со залиха
     */
    public function getAllProducts($user_role, $user_id) {
        $conn = $this->connect(); // Ја користи внатрешната OOP конекција од родителот
        
        $user_id = intval($user_id);
        $user_role = trim($user_role);
        
        // Сигурносен инвентарен упит со LEFT JOIN
        if ($user_role === 'admin') {
            // Продавачот ги гледа само своите сопствени продукти, со пресметана точна залиха
            $query = "SELECT p.*, 
                             IFNULL(SUM(ps.quantity), 0) as total_qty,
                             GROUP_CONCAT(CONCAT(ps.size_name, ':', ps.quantity)) as stock_summary
                      FROM `producttable` p
                      LEFT JOIN `product_stock` ps ON p.id = ps.product_id
                      WHERE p.admin_id = $user_id
                      GROUP BY p.id
                      ORDER BY p.id DESC";
        } else {
            // Купувачите и Root ги гледаат сите активни производи во продавницата со залиха
            $query = "SELECT p.*, 
                             IFNULL(SUM(ps.quantity), 0) as total_qty,
                             GROUP_CONCAT(CONCAT(ps.size_name, ':', ps.quantity)) as stock_summary
                      FROM `producttable` p
                      LEFT JOIN `product_stock` ps ON p.id = ps.product_id
                      GROUP BY p.id
                      ORDER BY p.id DESC";
        }
        
        $result = mysqli_query($conn, $query);
        $products = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $products[] = $row;
            }
        }
        
        return $products;
    }



    public function delete($all_id){
        $conn = $this->connect();
        
        $clean_ids = array_map('intval', $all_id);
        $id_list = implode(',', $clean_ids);
        
        $result = mysqli_query($conn, "DELETE FROM $this->tbname WHERE `id` IN (" . $id_list . ")");
        return (bool)$result;
    }
    
    public function getCartProducts($ids_array) {
        $conn = $this->connect();
        $array = [];
        
        if (empty($ids_array)) {
            return $array;
        }

        $clean_ids = array_map('intval', $ids_array);
        $ids_string = implode(',', $clean_ids);

        $query = "SELECT * FROM " . $this->tbname . " WHERE id IN ($ids_string)";
        $result = mysqli_query($conn, $query);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $array[] = $row;
            }
        }
        return $array;
    }

    // Земи ги сите корисници кои се продавачи (role = 'admin')
    public function getAllSellers() {
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

    // Ресетирање на лозинка на одреден продавач
    public function resetSellerPassword($userId, $newPlainPassword) {
        $conn = $this->connect();
        $userId = intval($userId);
        $hashedPassword = password_hash($newPlainPassword, PASSWORD_DEFAULT);
        $query = "UPDATE `users` SET `password` = '$hashedPassword' WHERE `id` = $userId AND `role` = 'admin'";
        return mysqli_query($conn, $query);
    }

    // Бришење на продавач (со CASCADE автоматски се чистат и продуктите)
    public function deleteSeller($userId) {
        $conn = $this->connect();
        $userId = intval($userId);
        $query = "DELETE FROM `users` WHERE `id` = $userId AND `role` = 'admin'";
        return mysqli_query($conn, $query);
    }
}
?>
