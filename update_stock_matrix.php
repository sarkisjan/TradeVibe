<?php
session_start();
header('Content-Type: application/json');
require_once "includes/autoloader.php";

// Безбедност: Опцијата е дозволена строго за Продавачи (admin)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized entry protocol restriction.']);
    exit();
}

$dbClass = new Database();
$conn = $dbClass->connect();

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data && isset($data['product_id']) && isset($data['size']) && isset($data['quantity_to_add'])) {
    $pId = intval($data['product_id']);
    $sizeName = mysqli_real_escape_string($conn, $data['size']);
    $qtyToAdd = intval($data['quantity_to_add']);
    $adminId = intval($_SESSION['user_id']);

    // СИГУРНОСЕН ГАРД: Обезбедуваме продавачот да може да менува залиха САМО НА СВОЈ производ
    $check = mysqli_query($conn, "SELECT `id` FROM `producttable` WHERE `id` = $pId AND `admin_id` = $adminId");
    
    if (mysqli_num_rows($check) > 0) {
        
        // Паметна SQL проверка: дали за овој специфичен број веќе има запис во табелата `product_stock`
        $stockCheck = mysqli_query($conn, "SELECT `id` FROM `product_stock` WHERE `product_id` = $pId AND `size_name` = '$sizeName'");
        
        if (mysqli_num_rows($stockCheck) > 0) {
            // А) АКО ПОСТОИ: Го зголемуваме моменталниот број на парчиња во магацинот
            $sql = "UPDATE `product_stock` SET `quantity` = `quantity` + $qtyToAdd WHERE `product_id` = $pId AND `size_name` = '$sizeName'";
        } else {
            // Б) АКО НЕ ПОСТОИ: Креираме нов ред за залиха
            $sql = "INSERT INTO `product_stock` (`product_id`, `size_name`, `quantity`) VALUES ($pId, '$sizeName', $qtyToAdd)";
        }

        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'You do not own this product inventory log matrix!']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data payload object structure.']);
}
exit();
?>
