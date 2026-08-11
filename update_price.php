<?php
session_start();
// Set JSON communication header immediately for the frontend fetch request
header('Content-Type: application/json');

require_once "includes/autoloader.php";

// Security: Check if the logged-in user is an admin (seller)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access control protocols.']);
    exit();
}

// Connect to the database
$dbClass = new Database();
$conn = $dbClass->connect();

// Read incoming raw JSON data from the frontend request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Verify that all required fields are present in the incoming payload
if (isset($data['product_id']) && isset($data['price']) && isset($data['discount'])) {
    $productId = intval($data['product_id']);
    $newPrice  = floatval($data['price']);
    $newDiscount = intval($data['discount']);
    $currentAdminId = intval($_SESSION['user_id']);

    // Security Check: Verify if this product really belongs to the logged-in seller
    $productCheck = mysqli_query($conn, "SELECT `admin_id` FROM `producttable` WHERE `id` = $productId");
    $product = mysqli_fetch_assoc($productCheck);

    if ($product && intval($product['admin_id']) === $currentAdminId) {
        // Execute the database update for price and discount fields
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
