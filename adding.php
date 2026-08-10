<?php
session_start();

// Оневозможуваме грешките да го расипат JSON излезот
ini_set('display_errors', 0); 
error_reporting(E_ALL);

require_once "includes/autoloader.php";

$errors = [];

// Верификација на влезните FormData податоци
if (isset($_POST['productData'])) {
    $dataArray = json_decode($_POST['productData'], true);
    $data = (object)$dataArray;
} else {
    $data = null;
}

if ($data && isset($data->productSave)) {
    
   
    $raw_sub = isset($data->subcategory) ? trim($data->subcategory) : '';
    $type = ucfirst(strtolower($raw_sub)); 
    


    // Го запишуваме назад во објектот за табелата во базата да си работи нормално
    $data->productType = $type; 

    // Валидација на задолжителните категории
    if (empty($data->category)) { $errors['category'] = "Please select a main category."; }
    if (empty($type)) { $errors['subcategory'] = "Please select a subcategory."; }

    // ТЕХНИЧКА ВАЛИДАЦИЈА: Автоматски ја повикува точната сопствена класа од Чекор 2
    if (empty($errors)) {
        if (class_exists($type)) {
            $product = new $type();
            $errors = $product->validateForm($data);
        } else {
            $errors['subcategory'] = "Technical product validation class [{$type}] not found.";
        }
    }

    // МЕНАЏИРАЊЕ НА MULTIPLE UPLOAD НА СЛИКИ (Идентично како претходно)
    $uploadedImages = [];
    if (empty($errors) && isset($_FILES['product_image']) && is_array($_FILES['product_image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        foreach ($_FILES['product_image']['name'] as $key => $name) {
            if ($_FILES['product_image']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $uniqueName = time() . '_' . uniqid() . '.' . $ext;
                    $targetDir = "uploads/";
                    if (!is_dir($targetDir)) { mkdir($targetDir, 0777, true); }
                    if (move_uploaded_file($_FILES['product_image']['tmp_name'][$key], $targetDir . $uniqueName)) {
                        $uploadedImages[] = $uniqueName;
                    }
                }
            }
        }
    }

    $imageName = !empty($uploadedImages) ? implode(',', $uploadedImages) : 'default-product.png';

    // ЗАЧУВУВАЊЕ ВО БАЗА (Ако сè е во ред)
    if (empty($errors)) {
        $data->image = $imageName;
        $data->admin_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

        if ($data->admin_id === 0) {
            $errors['system'] = "Session expired. Please log in again.";
            header('Content-Type: application/json'); echo json_encode($errors); exit();
        }

        $process = new Process();
        $process->saveProduct($data); 
    }
} else {
    $errors['system'] = "No product data payload received.";
}


// Испраќање на чист JSON назад до app.js
header('Content-Type: application/json');
echo json_encode($errors);
exit();
?>
