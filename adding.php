<?php
session_start();

// Prevent PHP errors from breaking the JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once "includes/autoloader.php";

$errors = [];

// Read and decode the product data sent with FormData
if (isset($_POST['productData'])) {
    $dataArray = json_decode($_POST['productData'], true);
    $data = (object)$dataArray;
} else {
    $data = null;
}

// Check if a product save request was received
if ($data && isset($data->productSave)) {

    // Get and normalize the product subcategory
    $raw_sub = isset($data->subcategory) ? trim($data->subcategory) : '';
    $type = ucfirst(strtolower($raw_sub));



    // Store the product type for database processing
    $data->productType = $type;

    // Validate the required category fields
    if (empty($data->category)) {
        $errors['category'] = "Please select a main category.";
    }
    if (empty($type)) {
        $errors['subcategory'] = "Please select a subcategory.";
    }

    // Use the product type to select the correct validation class
    if (empty($errors)) {
        if (class_exists($type)) {
            $product = new $type();
            $errors = $product->validateForm($data);
        } else {
            $errors['subcategory'] = "Technical product validation class [{$type}] not found.";
        }
    }

    // Handle multiple product image uploads
    $uploadedImages = [];

    if (empty($errors) && isset($_FILES['product_image']) && is_array($_FILES['product_image']['name'])) {

        // Allowed image file types
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        foreach ($_FILES['product_image']['name'] as $key => $name) {

            // Process only successfully uploaded files
            if ($_FILES['product_image']['error'][$key] === UPLOAD_ERR_OK) {

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                // Check if the file type is allowed
                if (in_array($ext, $allowed)) {

                    // Generate a unique file name
                    $uniqueName = time() . '_' . uniqid() . '.' . $ext;

                    $targetDir = "uploads/";

                    // Create the upload directory if it does not exist
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }

                    // Move the uploaded file to the uploads directory
                    if (move_uploaded_file($_FILES['product_image']['tmp_name'][$key], $targetDir . $uniqueName)) {
                        $uploadedImages[] = $uniqueName;
                    }
                }
            }
        }
    }

    // Use the default image if no images were uploaded
    $imageName = !empty($uploadedImages) ? implode(',', $uploadedImages) : 'default-product.png';

    // Save the product after all validation has passed
    if (empty($errors)) {

        // Get the logged-in user's ID
        $data->image = $imageName;
        $data->admin_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

        // Make sure the user session is still valid
        if ($data->admin_id === 0) {
            $errors['system'] = "Session expired. Please log in again.";
            header('Content-Type: application/json');
            echo json_encode($errors);
            exit();
        }

        // Save the product to the database
        $process = new Process();
        $process->saveProduct($data);
    }
} else {
    // Return an error if no product data was received
    $errors['system'] = "No product data payload received.";
}


// Return validation or system errors as JSON to app.js
header('Content-Type: application/json');
echo json_encode($errors);
exit();
