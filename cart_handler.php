<?php
session_start();

// Create an empty cart if the session cart does not exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Read and decode the JSON request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Default response
$response = ['success' => false, 'cart_count' => 0];

if ($data && isset($data['action']) && isset($data['product_id'])) {
    $id = intval($data['product_id']);
    $action = $data['action'];
    $size = isset($data['size']) ? trim($data['size']) : 'Standard';

    // Add a product to the cart
    if ($action === 'add') {
        // Increase the quantity if the product is already in the cart
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['qty'] += 1;
        } else {
            // Store the product quantity and selected size
            $_SESSION['cart'][$id] = [
                'qty' => 1,
                'size' => $size
            ];
        }
        $response['success'] = true;
    }
    // Update the quantity of an existing product
    elseif ($action === 'update' && isset($data['quantity'])) {
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['qty'] = intval($data['quantity']);
            $response['success'] = true;
        }
    }
    // Remove a product from the cart
    elseif ($action === 'remove') {
        if (isset($_SESSION['cart'][$id])) {
            unset($_SESSION['cart'][$id]);
            $response['success'] = true;
        }
    }
}

// Calculate the total number of items in the cart
$total_items = 0;

foreach ($_SESSION['cart'] as $item) {
    $total_items += (is_array($item) ? $item['qty'] : $item);
}
$response['cart_count'] = $total_items;

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($response);

exit();
