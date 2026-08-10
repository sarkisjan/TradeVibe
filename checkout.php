<?php
session_start();
require_once "includes/autoloader.php";
require_once "classes/Currency.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$dbClass = new Database();
$conn = $dbClass->connect();

$userId = $_SESSION['user_id'];
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$currency = isset($_SESSION['currency']) ? $_SESSION['currency'] : 'USD';

if (empty($cart_items) || !isset($_POST['delivery_address'])) {
    header("Location: view_cart.php");
    exit();
}

// Прифаќање на потврдените податоци од модалот
$delName = mysqli_real_escape_string($conn, $_POST['delivery_name']);
$delAddress = mysqli_real_escape_string($conn, $_POST['delivery_address']);
$delPhone = mysqli_real_escape_string($conn, $_POST['delivery_phone']);
$payMethod = mysqli_real_escape_string($conn, $_POST['payment_method']);

$product_ids = array_keys($cart_items);
$process = new Process();
$products = $process->getCartProducts($product_ids);

$total_price = 0;
$order_items_to_save = [];
$email_items_html = ""; // За е-мејл потврдата

foreach ($products as $product) {
    $id = $product['id'];
    $qty = isset($cart_items[$id]['qty']) ? intval($cart_items[$id]['qty']) : 1;
    $size = isset($cart_items[$id]['size']) ? $cart_items[$id]['size'] : 'Standard';
    
    $clean_price = str_replace(['$', ' ', 'den', 'EUR', '€'], '', $product['price']);
    $item_price = floatval($clean_price);
    
    if (intval($product['discount']) > 0) {
        $item_price = $item_price - ($item_price * (intval($product['discount']) / 100));
    }
    
    $converted_price = Currency::convert($item_price, $currency);
    $subtotal = $converted_price * $qty;
    $total_price += $subtotal;
    
    $order_items_to_save[] = [
        'product_id' => $id,
        'quantity' => $qty,
        'price' => $converted_price,
        'size' => $size
    ];

    // Градење на редовите за е-мејл известувањето
    $currency_symbol = Currency::getSymbol($currency);
    $email_items_html .= "
    <tr>
        <td style='padding:10px; border:1px solid #ddd;'>{$product['name']}</td>
        <td style='padding:10px; border:1px solid #ddd;'>{$product['sku']}</td>
        <td style='padding:10px; border:1px solid #ddd;'>{$size}</td>
        <td style='padding:10px; border:1px solid #ddd;'>{$qty}</td>
        <td style='padding:10px; border:1px solid #ddd;'>".number_format($converted_price, 2)." {$currency_symbol}</td>
    </tr>";
}
// 1. ПАМЕТЕН ФИКС: Го извлекуваме симболот најгоре за да биде достапен и за двата типа корисници
$currency_symbol = Currency::getSymbol($currency);

$shipping_address = mysqli_real_escape_string($conn, $_SESSION['address']);

// 2. ЗАПИШУВАЊЕ ВО ГЛАВНАТА ТАБЕЛА ЗА НАРАЧКИ (orders)
$order_query = "INSERT INTO `orders` (`user_id`, `total_amount`, `currency`, `shipping_address`, `shipping_name`, `shipping_phone`, `payment_method`, `status`, `order_date`) 
                VALUES ($userId, $total_price, '$currency', '$delAddress', '$delName', '$delPhone', '$payMethod', 'Pending', CURRENT_TIMESTAMP)";


if (mysqli_query($conn, $order_query)) {
    $order_id = mysqli_insert_id($conn);
    
    // 1. ЗАПИШУВАЊЕ ВО order_items И ИНСТАНТНО НАМАЛУВАЊЕ НА ЗАЛИХИТЕ ВО МАГАЦИНОТ
    foreach ($order_items_to_save as $item) {
        $pId   = $item['product_id']; 
        $pQty  = $item['quantity']; 
        $pPrice = $item['price']; 
        $pSize  = mysqli_real_escape_string($conn, $item['size']);
        
        // А) Внесување на ставката во историјата на купувања
        $item_query = "INSERT INTO `order_items` (`order_id`, `product_id`, `quantity`, `price_at_purchase`, `selected_size`) 
                       VALUES ($order_id, $pId, $pQty, $pPrice, '$pSize')";
        mysqli_query($conn, $item_query);

        // Б) КРИТИЧЕН ФИКС: Одземање на купените парчиња од инвентарот во реално време
        $update_stock_query = "UPDATE `product_stock` 
                               SET `quantity` = `quantity` - $pQty 
                               WHERE `product_id` = $pId AND `size_name` = '$pSize'";
        mysqli_query($conn, $update_stock_query);
    }
    
    // ==========================================================================
    // 2. ПАМЕТЕН ФИКС ЗА ТАБЕЛАТА CART: Го чистиме купениот инвентар од базата
    // ==========================================================================
    $clear_db_cart_query = "DELETE FROM `cart` WHERE `user_id` = $userId";
    mysqli_query($conn, $clear_db_cart_query);

    // ==========================================================================
    // 3. СИГУРНОСНА ПРОВЕРКА ЗА ДЕМО / ФИКТИВНИ ТЕСТ МЕЈЛОВИ
    // ==========================================================================
    $buyer_email = strtolower(trim($_SESSION['email']));
    $fictional_emails = ['user@eshop.com', 'admin@eshop.com', 'root@eshop.com', 'seller2@eshop.com', 'buyer2@eshop.com'];
    
    $alert_notice_english = "Thank you for your purchase! Order ID: #".$order_id." - Total Paid: ".number_format($total_price, 2)." ".$currency_symbol."\\n\\n";

    if (in_array($buyer_email, $fictional_emails)) {
        // АКО Е ТЕСТ КОРИСНИК: Ја прескокнуваме mail() функцијата и ја лепиме твојата порака на англиски
        $alert_notice_english .= "This is a test user with a non-existent/fictional email address, order confirmation has not been sent. If you want to see what the order confirmation looks like, please register with an existing email address.";
    } else {
        // АКО Е ВИСТИНСКИ КОРИСНИК: Му испраќаме официјален е-мејл
        $to = $_SESSION['email'];
        $subject = "Order Confirmation #{$order_id} - TradeVibe Market";
        
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
            <h3 style='text-align:right; margin-top:20px;'>Total Amount Paid: ".number_format($total_price, 2)." {$currency_symbol}</h3>
        </body>
        </html>";

        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: orders@tradevibe-market.com\r\n";
        
        mail($to, $subject, $message, $headers);
        $alert_notice_english .= "Order confirmation email has been successfully dispatched to your email address inbox!";
    }

    $_SESSION['cart'] = []; // Го празниме привремениот сесиски кеш на кошничката
    
    // ИСПРИНТ НА КРАЈНИОТ АЛЕРТ ПРОЗОРЕЦ СО ТВОЈОТ АНГЛИСКИ ТЕКСТ
    echo "
    <script>
        alert('".$alert_notice_english."');
        window.location.href = 'orders_history.php';
    </script>";
} else {
    echo "Checkout System Exception Error: " . mysqli_error($conn);
}
exit();
?>

