<?php

session_start();

require_once "includes/autoloader.php";
require_once "classes/Currency.php";

// Only logged-in customers can complete checkout
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header("Location: login.php");
    exit();
}

// Connect to database
$dbClass = new Database();
$conn = $dbClass->connect();

if (!$conn) {
    die("Database connection failed.");
}

$userId = intval($_SESSION['user_id']);

$cart_items = isset($_SESSION['cart'])
    ? $_SESSION['cart']
    : [];

$currency = isset($_SESSION['currency'])
    ? $_SESSION['currency']
    : 'USD';

// Make sure cart and checkout data exist
if (
    empty($cart_items) ||
    !isset($_POST['delivery_address']) ||
    !isset($_POST['delivery_name']) ||
    !isset($_POST['delivery_phone']) ||
    !isset($_POST['payment_method'])
) {
    header("Location: view_cart.php");
    exit();
}

// Get checkout form data
$delName = mysqli_real_escape_string(
    $conn,
    trim($_POST['delivery_name'])
);

$delAddress = mysqli_real_escape_string(
    $conn,
    trim($_POST['delivery_address'])
);

$delPhone = mysqli_real_escape_string(
    $conn,
    trim($_POST['delivery_phone'])
);

$payMethod = mysqli_real_escape_string(
    $conn,
    trim($_POST['payment_method'])
);


/*
 * ==========================================================
 * GET PRODUCT IDS FROM THE NEW CART STRUCTURE
 * ==========================================================
 *
 * Cart structure:
 *
 * $_SESSION['cart'] = [
 *
 *     '25_42' => [
 *         'product_id' => 25,
 *         'qty' => 1,
 *         'size' => '42'
 *     ],
 *
 *     '25_39' => [
 *         'product_id' => 25,
 *         'qty' => 1,
 *         'size' => '39'
 *     ]
 * ];
 */

$product_ids = [];

foreach ($cart_items as $cartItem) {

    if (
        is_array($cartItem) &&
        isset($cartItem['product_id'])
    ) {

        $product_ids[] =
            intval($cartItem['product_id']);
    }
}

$product_ids = array_unique($product_ids);

if (empty($product_ids)) {
    header("Location: view_cart.php");
    exit();
}


/*
 * ==========================================================
 * GET PRODUCTS FROM DATABASE
 * ==========================================================
 */

$process = new Process();

$products =
    $process->getCartProducts($product_ids);

if (empty($products)) {
    header("Location: view_cart.php");
    exit();
}


/*
 * ==========================================================
 * PREPARE ORDER DATA
 * ==========================================================
 */

$total_price = 0;

$order_items_to_save = [];

$email_items_html = "";

$currency_symbol =
    Currency::getSymbol($currency);


/*
 * ==========================================================
 * PROCESS EVERY CART ITEM
 * ==========================================================
 *
 * IMPORTANT:
 *
 * We loop through $cart_items, NOT $products.
 *
 * This is because the same product can exist multiple
 * times with different sizes.
 *
 * Example:
 *
 * Hummel shoes - 42
 * Hummel shoes - 39
 *
 * Both have the same product_id but are separate
 * cart items.
 */

foreach ($cart_items as $cartKey => $cartItem) {

    if (
        !is_array($cartItem) ||
        !isset($cartItem['product_id'])
    ) {
        continue;
    }

    $id = intval($cartItem['product_id']);

    $qty = isset($cartItem['qty'])
        ? intval($cartItem['qty'])
        : 1;

    $size = isset($cartItem['size']) &&
        trim($cartItem['size']) !== ''
        ? trim($cartItem['size'])
        : 'Standard';

    /*
     * Quantity must always be positive.
     */
    if ($qty <= 0) {
        continue;
    }


    /*
     * Find the product information.
     */
    $product = null;

    foreach ($products as $p) {

        if (intval($p['id']) === $id) {

            $product = $p;
            break;
        }
    }

    if (!$product) {
        continue;
    }


    /*
     * ======================================================
     * CALCULATE PRODUCT PRICE
     * ======================================================
     */

    $clean_price = str_replace(
        ['$', ' ', 'den', 'EUR', '€'],
        '',
        $product['price']
    );

    $item_price = floatval($clean_price);


    /*
     * Apply discount.
     */
    $discount = intval($product['discount']);

    if ($discount > 0) {

        $item_price =
            $item_price -
            ($item_price * ($discount / 100));
    }


    /*
     * Convert to selected currency.
     */
    $converted_price =
        Currency::convert(
            $item_price,
            $currency
        );


    /*
     * Calculate subtotal for THIS size.
     */
    $subtotal =
        $converted_price * $qty;

    $total_price += $subtotal;


    /*
     * ======================================================
     * SAVE ORDER ITEM DATA
     * ======================================================
     */

    $order_items_to_save[] = [

        'product_id' => $id,

        'quantity' => $qty,

        'price' => $converted_price,

        'size' => $size
    ];


    /*
     * ======================================================
     * BUILD CONFIRMATION EMAIL
     * ======================================================
     */

    $safe_product_name =
        htmlspecialchars(
            $product['name'],
            ENT_QUOTES,
            'UTF-8'
        );

    $safe_sku =
        htmlspecialchars(
            $product['sku'],
            ENT_QUOTES,
            'UTF-8'
        );

    $safe_size =
        htmlspecialchars(
            $size,
            ENT_QUOTES,
            'UTF-8'
        );

    $email_items_html .= "

    <tr>

        <td style='padding:10px; border:1px solid #ddd;'>
            {$safe_product_name}
        </td>

        <td style='padding:10px; border:1px solid #ddd;'>
            {$safe_sku}
        </td>

        <td style='padding:10px; border:1px solid #ddd;'>
            {$safe_size}
        </td>

        <td style='padding:10px; border:1px solid #ddd;'>
            {$qty}
        </td>

        <td style='padding:10px; border:1px solid #ddd;'>
            " . number_format(
        $converted_price,
        2
    ) . " {$currency_symbol}
        </td>

    </tr>";
}


/*
 * ==========================================================
 * SHIPPING
 * ==========================================================
 */

$shipping_threshold = 50.00;
$standard_shipping_rate = 3.00;

if (
    $currency === 'MKD' ||
    $currency === 'ден'
) {

    $shipping_threshold = 2500.00;
    $standard_shipping_rate = 150.00;
} elseif (
    $currency === 'EUR' ||
    $currency === '€'
) {

    $shipping_threshold = 42.00;
    $standard_shipping_rate = 2.50;
}


/*
 * Calculate shipping.
 */

$shipping_cost =
    ($total_price >= $shipping_threshold)
    ? 0.00
    : $standard_shipping_rate;


/*
 * Final total.
 */

$final_grand_total =
    $total_price + $shipping_cost;


/*
 * ==========================================================
 * CREATE ORDER
 * ==========================================================
 */

$order_query = "

INSERT INTO orders

(
    user_id,
    total_amount,
    currency,
    shipping_address,
    shipping_name,
    shipping_phone,
    payment_method,
    status,
    order_date
)

VALUES

(
    $userId,
    $final_grand_total,
    '$currency',
    '$delAddress',
    '$delName',
    '$delPhone',
    '$payMethod',
    'Pending',
    CURRENT_TIMESTAMP
)

";


if (mysqli_query($conn, $order_query)) {

    $order_id =
        mysqli_insert_id($conn);


    /*
     * ======================================================
     * SAVE ORDER ITEMS + REDUCE STOCK
     * ======================================================
     */

    foreach ($order_items_to_save as $item) {

        $pId =
            intval($item['product_id']);

        $pQty =
            intval($item['quantity']);

        $pPrice =
            floatval($item['price']);

        $pSize =
            mysqli_real_escape_string(
                $conn,
                $item['size']
            );


        /*
         * Save order item.
         */

        $item_query = "

        INSERT INTO order_items

        (
            order_id,
            product_id,
            quantity,
            price_at_purchase,
            selected_size
        )

        VALUES

        (
            $order_id,
            $pId,
            $pQty,
            $pPrice,
            '$pSize'
        )

        ";

        mysqli_query(
            $conn,
            $item_query
        );


        /*
         * ==================================================
         * REDUCE STOCK FOR THIS EXACT SIZE
         * ==================================================
         */

        $update_stock_query = "

        UPDATE product_stock

        SET quantity = quantity - $pQty

        WHERE product_id = $pId

        AND size_name = '$pSize'

        AND quantity >= $pQty

        ";

        mysqli_query(
            $conn,
            $update_stock_query
        );
    }


    /*
     * ======================================================
     * CLEAR DATABASE CART
     * ======================================================
     */

    $clear_db_cart_query = "

    DELETE FROM cart

    WHERE user_id = $userId

    ";

    mysqli_query(
        $conn,
        $clear_db_cart_query
    );


    /*
     * ======================================================
     * ORDER CONFIRMATION EMAIL
     * ======================================================
     */

    $buyer_email =
        strtolower(
            trim($_SESSION['email'])
        );


    $fictional_emails = [

        'user@eshop.com',
        'admin@eshop.com',
        'root@eshop.com',
        'seller2@eshop.com',
        'buyer2@eshop.com'

    ];


    $alert_notice_english =
        "Thank you for your purchase! " .
        "Order ID: #" .
        $order_id .
        " - Total Paid: " .
        number_format(
            $final_grand_total,
            2
        ) .
        " " .
        $currency_symbol .
        "\\n\\n";


    if (
        in_array(
            $buyer_email,
            $fictional_emails
        )
    ) {

        $alert_notice_english .=
            "This is a test user with a " .
            "non-existent/fictional email address, " .
            "order confirmation has not been sent. " .
            "If you want to see what the order confirmation " .
            "looks like, please register with an existing " .
            "email address.";
    } else {

        /*
         * Send confirmation email.
         */

        $to =
            $_SESSION['email'];

        $subject =
            "Order Confirmation #{$order_id} - TradeVibe Market";


        $message = "

        <html>

        <body
            style='font-family:sans-serif;color:#1a202c;'
        >

            <h2>
                Thank you for your order, {$delName}!
            </h2>

            <p>
                Your order has been registered successfully
                and is currently being processed by our
                logistics matrix.
            </p>

            <h3>
                Order Summary (ID: #{$order_id})
            </h3>

            <p>
                <strong>Shipping Address:</strong>
                {$delAddress}
                <br>

                <strong>Phone:</strong>
                {$delPhone}
                <br>

                <strong>Payment Method:</strong>
                {$payMethod}
            </p>

            <table
                style='width:100%;
                       border-collapse:collapse;
                       font-size:13px;'
            >

                <thead>

                    <tr
                        style='background:#f5f5f5;'
                    >

                        <th
                            style='padding:10px;
                                   border:1px solid #ddd;'
                        >
                            Product
                        </th>

                        <th
                            style='padding:10px;
                                   border:1px solid #ddd;'
                        >
                            SKU
                        </th>

                        <th
                            style='padding:10px;
                                   border:1px solid #ddd;'
                        >
                            Size
                        </th>

                        <th
                            style='padding:10px;
                                   border:1px solid #ddd;'
                        >
                            Qty
                        </th>

                        <th
                            style='padding:10px;
                                   border:1px solid #ddd;'
                        >
                            Price
                        </th>

                    </tr>

                </thead>

                <tbody>

                    {$email_items_html}

                </tbody>

            </table>

            <h3
                style='text-align:right;margin-top:20px;'
            >
                Total Amount Paid:
                " .
            number_format(
                $final_grand_total,
                2
            ) .
            " {$currency_symbol}

            </h3>

        </body>

        </html>

        ";


        $headers =
            "MIME-Version: 1.0\r\n" .
            "Content-type:text/html;charset=UTF-8\r\n" .
            "From: orders@tradevibe-market.com\r\n";


        mail(
            $to,
            $subject,
            $message,
            $headers
        );


        $alert_notice_english .=
            "Order confirmation email has been successfully " .
            "dispatched to your email address inbox!";
    }


    /*
     * ======================================================
     * CLEAR SESSION CART
     * ======================================================
     */

    $_SESSION['cart'] = [];


    /*
     * ======================================================
     * REDIRECT TO ORDER HISTORY
     * ======================================================
     */

    echo "

    <script>

        alert('" .
        addslashes(
            $alert_notice_english
        ) .
        "');

        window.location.href =
            'orders_history.php';

    </script>

    ";
} else {

    echo
    "Checkout System Exception Error: " .
        mysqli_error($conn);
}

exit();
