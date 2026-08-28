<?php
// ENFORCE ERROR LOGGING: Присилно прикажување на скриени грешки во живо на серверот
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SECURITY HEADERS: Ги отклучуваме хостинг филтрите за асинхрони AJAX повици
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('X-Requested-With: XMLHttpRequest');

// ABSOLUTE DIRECTORY INJECTION LAYERS: Прескокнување на автолоудерот кој паѓа на Linux хостинг сервери
require_once __DIR__ . "/classes/Database.php";

// Security Protection: Check if the logged-in user session context matches an admin profile
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized entry protocol restriction.']);
    exit();
}

// Initialize core paren OOP database connector system
$dbClass = new Database();
$conn = $dbClass->connect();

// ENCODING PROTOCOL LOCK: Форсираме чист UTF-8 проток на стринговите за да спречиме колапс на json_encode
mysqli_set_charset($conn, "utf8mb4");

// Read the raw inbound asynchronous fetch JSON body string stream safely
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data && isset($data['product_id']) && isset($data['size']) && isset($data['quantity_to_add'])) {
    $pId = intval($data['product_id']);
    $sizeName = mysqli_real_escape_string($conn, trim($data['size']));
    $qtyToAdd = intval($data['quantity_to_add']);
    $adminId = intval($_SESSION['user_id']);

    // Check ownership: Ensure the current logged-in seller profile owns this specific product inside the schema layout
    $check = mysqli_query($conn, "SELECT `id` FROM `producttable` WHERE `id` = $pId AND `admin_id` = $adminId");

    if ($check && mysqli_num_rows($check) > 0) {

        // Database check: See if this product size variation is already registered inside the product_stock table
        $stockCheck = mysqli_query($conn, "SELECT `id` FROM `product_stock` WHERE `product_id` = $pId AND `size_name` = '$sizeName'");

        if ($stockCheck && mysqli_num_rows($stockCheck) > 0) {
            // If the size variation exists inside warehouse logs, execute an incremental SQL UPDATE query safely
            $sql = "UPDATE `product_stock` SET `quantity` = `quantity` + $qtyToAdd WHERE `product_id` = $pId AND `size_name` = '$sizeName'";
        } else {
            // If the size variation does not exist inside warehouse registries, execute a safe INSERT query directly
            $sql = "INSERT INTO `product_stock` (`product_id`, `size_name`, `quantity`) VALUES ($pId, '$sizeName', $qtyToAdd)";
        }

        if (mysqli_query($conn, $sql)) {
            // Success flag transaction complete
            echo json_encode(['success' => true, 'message' => 'Warehouse stock metrics adjusted cleanly inside the database.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database operation query crash encounted: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Security Guard: You do not own this specific product inventory log matrix!']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid structural outbound payload data object schema detected.']);
}
exit();
