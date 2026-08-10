<?php
session_start();
// ВЕДНАШ СИГУРНО ПОСТАВУВАМЕ JSON КОМУНИКАЦИЈА СО PREVIEW ТАБОТ
header('Content-Type: application/json');

require_once "includes/autoloader.php";

// Безбедносна кочница: Корисникот мора да биде логиран како продавач
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access control protocols.']);
    exit();
}

$dbClass = new Database();
$conn = $dbClass->connect();

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (isset($data['product_id']) && isset($data['price']) && isset($data['discount'])) {
    $productId = intval($data['product_id']);
    $newPrice  = floatval($data['price']);
    $newDiscount = intval($data['discount']);
    $currentAdminId = intval($_SESSION['user_id']);

    // СИГУРНОСНА ПРОВЕРКА: Проверуваме дали овој продукт навистина му припаѓа на овој админ
    $productCheck = mysqli_query($conn, "SELECT `admin_id` FROM `producttable` WHERE `id` = $productId");
    $product = mysqli_fetch_assoc($productCheck);

    if ($product && intval($product['admin_id']) === $currentAdminId) {
        // Извршување на UPDATE во базата на податоци
        $sql = "UPDATE `producttable` SET `price` = $newPrice, `discount` = $newDiscount WHERE `id` = $productId";
        
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update error query: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'You do not own this product authorization matrix!']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data payload object received.']);
}
exit();
?>
