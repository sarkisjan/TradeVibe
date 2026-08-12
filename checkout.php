<?php
session_start();
require_once "includes/autoloader.php";
require_once "classes/Currency.php";

// Only logged-in customers can complete checkout
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: login.php");
    exit();
}

// Connect to the database
$dbClass = new Database();
$conn = $dbClass->connect();

$userId = $_SESSION['user_id'];
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$currency = isset($_SESSION['currency']) ? $_SESSION['currency'] : 'USD';

// Make sure the cart and delivery address are available
if (empty($cart_items) || !isset($_POST['delivery_address'])) {
    header("Location: view_cart.php");
    exit();
}

// Get the delivery and payment details from the checkout form
$delName = mysqli_real_escape_string($conn, $_POST['delivery_name']);
$delAddress = mysqli_real_escape_string($conn, $_POST['delivery_address']);
$delPhone = mysqli_real_escape_string($conn, $_POST['delivery_phone']);
$payMethod = mysqli_real_escape_string($conn, $_POST['payment_method']);

// Get the products currently in the cart
$product_ids = array_keys($cart_items);

$process = new Process();
$products = $process->getCartProducts($product_ids);

$total_price = 0;
$order_items_to_save = [];
$email_items_html = "";

// Calculate prices and prepare order items
foreach ($products as $product) {
    $id = $product['id'];
    $qty = isset($cart_items[$id]['qty']) ? intval($cart_items[$id]['qty']) : 1;
    $size = isset($cart_items[$id]['size']) ? $cart_items[$id]['size'] : 'Standard';

    // Remove currency characters before converting the price to a number
    $clean_price = str_replace(['$', ' ', 'den', 'EUR', '€'], '', $product['price']);
    $item_price = floatval($clean_price);

    // Apply the product discount
    if (intval($product['discount']) > 0) {
        $item_price = $item_price - ($item_price * (intval($product['discount']) / 100));
    }

    // Convert the price to the selected currency
    $converted_price = Currency::convert($item_price, $currency);

    $subtotal = $converted_price * $qty;
    $total_price += $subtotal;

    // Store the item data for the order_items table
    $order_items_to_save[] = [
        'product_id' => $id,
        'quantity' => $qty,
        'price' => $converted_price,
        'size' => $size
    ];

    // Build the product rows for the confirmation email
    $currency_symbol = Currency::getSymbol($currency);

    $email_items_html .= "
    <tr>
        <td style='padding:10px; border:1px solid #ddd;'>{$product['name']}</td>
        <td style='padding:10px; border:1px solid #ddd;'>{$product['sku']}</td>
        <td style='padding:10px; border:1px solid #ddd;'>{$size}</td>
        <td style='padding:10px; border:1px solid #ddd;'>{$qty}</td>
        <td style='padding:10px; border:1px solid #ddd;'>" . number_format($converted_price, 2) . " {$currency_symbol}</td>
    </tr>";
}

// Get the currency symbol for the order confirmation
$currency_symbol = Currency::getSymbol($currency);

// Set up shipping threshold parameters inspired by Ananas.mk marketplace
$shipping_threshold = 30.00;
$standard_shipping_rate = 3.00;

if ($currency === 'MKD' || $currency === 'ден') {
    $shipping_threshold = 1500.00;
    $standard_shipping_rate = 150.00;
} elseif ($currency === 'EUR' || $currency === '€') {
    $shipping_threshold = 25.00;
    $standard_shipping_rate = 2.50;
}

// Calculate dynamic shipping cost based on the cart subtotal balance threshold
$shipping_cost = ($total_price >= $shipping_threshold) ? 0.00 : $standard_shipping_rate;

// Enforce final grand total math including the delivery shipping fee rates
$final_grand_total = $total_price + $shipping_cost;

$shipping_address = mysqli_real_escape_string($conn, $_SESSION['address']);

// Create the main order record with the grand total including shipping
$order_query = "INSERT INTO `orders` (`user_id`, `total_amount`, `currency`, `shipping_address`, `shipping_name`, `shipping_phone`, `payment_method`, `status`, `order_date`) 
                VALUES ($userId, $final_grand_total, '$currency', '$delAddress', '$delName', '$delPhone', '$payMethod', 'Pending', CURRENT_TIMESTAMP)";


if (mysqli_query($conn, $order_query)) {
    $order_id = mysqli_insert_id($conn);

    // Save each product and reduce its stock
    foreach ($order_items_to_save as $item) {
        $pId   = $item['product_id'];
        $pQty  = $item['quantity'];
        $pPrice = $item['price'];
        $pSize  = mysqli_real_escape_string($conn, $item['size']);

        // Save the purchased product in the order history
        $item_query = "INSERT INTO `order_items` (`order_id`, `product_id`, `quantity`, `price_at_purchase`, `selected_size`) 
                       VALUES ($order_id, $pId, $pQty, $pPrice, '$pSize')";
        mysqli_query($conn, $item_query);

        // Reduce the available stock for the purchased product
        $update_stock_query = "UPDATE `product_stock` 
                               SET `quantity` = `quantity` - $pQty 
                               WHERE `product_id` = $pId AND `size_name` = '$pSize'";
        mysqli_query($conn, $update_stock_query);
    }

    // Remove the user's cart from the database after checkout
    $clear_db_cart_query = "DELETE FROM `cart` WHERE `user_id` = $userId";
    mysqli_query($conn, $clear_db_cart_query);

    // Prepare the order confirmation message
    $buyer_email = strtolower(trim($_SESSION['email']));

    // These accounts use fictional emails for testing
    $fictional_emails = ['user@eshop.com', 'admin@eshop.com', 'root@eshop.com', 'seller2@eshop.com', 'buyer2@eshop.com'];

    $alert_notice_english = "Thank you for your purchase! Order ID: #" . $order_id . " - Total Paid: " . number_format($total_price, 2) . " " . $currency_symbol . "\\n\\n";

    // Do not send emails to fictional test accounts
    if (in_array($buyer_email, $fictional_emails)) {

        $alert_notice_english .= "This is a test user with a non-existent/fictional email address, order confirmation has not been sent. If you want to see what the order confirmation looks like, please register with an existing email address.";
    } else {
        // Send the order confirmation to the customer's email
        $to = $_SESSION['email'];
        $subject = "Order Confirmation #{$order_id} - TradeVibe Market";

        // Build the HTML email
        $message = "
        <html>
        <body style='font-family: sans-serif; color: #1a202c;'>
            <h2>Thank you for your order, {$delName}!</h2>
            <p>Your order has been registered successfully and is currently being processed by our logistics matrix.</p>
            <h3>Order Summary (ID: #{$order_id})</h3>
            <p><strong>Shipping Address:</strong> {$delAddress}<br><strong>Phone:</strong> {$delPhone}<br><strong>Payment Method:</strong> {$payMethod}</p>
            <table style='width:100%; border-collapse:collapse; font-size:13px;'>
                <thead>
                    <tr style='background:#f5f5f5;'>
                        <th style='padding:10px; border:1px solid #ddd;'>Product</th>
                        <th style='padding:10px; border:1px solid #ddd;'>SKU</th>
                        <th style='padding:10px; border:1px solid #ddd;'>Size</th>
                        <th style='padding:10px; border:1px solid #ddd;'>Qty</th>
                        <th style='padding:10px; border:1px solid #ddd;'>Price</th>
                    </tr>
                </thead>
                <tbody>{$email_items_html}</tbody>
            </table>
            <h3 style='text-align:right; margin-top:20px;'>Total Amount Paid: " . number_format($total_price, 2) . " {$currency_symbol}</h3>
        </body>
        </html>";

        // Set the headers for the HTML email
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: orders@tradevibe-market.com\r\n";

        // Send the confirmation email
        mail($to, $subject, $message, $headers);

        $alert_notice_english .= "Order confirmation email has been successfully dispatched to your email address inbox!";
    }

    // Clear the cart stored in the current session
    $_SESSION['cart'] = [];

    // Show the order confirmation and redirect to order history
    echo "
    <script>
        alert('" . $alert_notice_english . "');
        window.location.href = 'orders_history.php';
    </script>";
} else {
    // Show the database error if the order could not be created
    echo "Checkout System Exception Error: " . mysqli_error($conn);
}
exit();
