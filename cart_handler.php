<?php
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$response = ['success' => false, 'cart_count' => 0];

if ($data && isset($data['action']) && isset($data['product_id'])) {
    $id = intval($data['product_id']);
    $action = $data['action'];
    $size = isset($data['size']) ? trim($data['size']) : 'Standard';

    if ($action === 'add') {
        // Зачувуваме и количина и големина заедно
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['qty'] += 1;
        } else {
            $_SESSION['cart'][$id] = [
                'qty' => 1,
                'size' => $size
            ];
        }
        $response['success'] = true;
    } 
    elseif ($action === 'update' && isset($data['quantity'])) {
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['qty'] = intval($data['quantity']);
            $response['success'] = true;
        }
    } 
    elseif ($action === 'remove') {
        if (isset($_SESSION['cart'][$id])) {
            unset($_SESSION['cart'][$id]);
            $response['success'] = true;
        }
    }
}

// Пресметка на вкупниот број на артикли во кошничката за иконата горе десно
$total_items = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_items += (is_array($item) ? $item['qty'] : $item);
}
$response['cart_count'] = $total_items;

header('Content-Type: application/json');
echo json_encode($response);
exit();
?>
