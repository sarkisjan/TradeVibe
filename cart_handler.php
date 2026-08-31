<?php

session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Read JSON request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$response = [
    'success' => false,
    'cart_count' => 0
];

if ($data && isset($data['action']) && isset($data['product_id'])) {

    $id = intval($data['product_id']);
    $action = $data['action'];

    $size = isset($data['size'])
        ? trim($data['size'])
        : 'Standard';

    /*
     * A product with different sizes must be treated
     * as a different cart item.
     *
     * Example:
     * 25_42 = Product 25, size 42
     * 25_39 = Product 25, size 39
     */
    $cartKey = $id . '_' . $size;

    require_once __DIR__ . "/classes/Database.php";

    $db = new Database();
    $conn = $db->connect();

    if (!$conn) {

        $response['message'] = 'Database connection failed.';
    } else {

        /*
         * ==========================================================
         * ADD PRODUCT
         * ==========================================================
         */

        if ($action === 'add') {

            $escaped_size = mysqli_real_escape_string($conn, $size);

            $stock_query = "
                SELECT quantity
                FROM product_stock
                WHERE product_id = $id
                AND size_name = '$escaped_size'
                LIMIT 1
            ";

            $stock_result = mysqli_query($conn, $stock_query);

            $stock_row = $stock_result
                ? mysqli_fetch_assoc($stock_result)
                : null;

            $available_stock = $stock_row
                ? intval($stock_row['quantity'])
                : 0;

            /*
             * Check how many pieces of THIS SIZE
             * are already in the cart.
             */
            $cart_quantity = isset($_SESSION['cart'][$cartKey])
                ? intval($_SESSION['cart'][$cartKey]['qty'])
                : 0;

            /*
             * Product/size is completely out of stock.
             */
            if ($available_stock <= 0) {

                $response['message'] =
                    'This size is out of stock.';

                /*
             * Prevent adding more than available stock.
             */
            } elseif (($cart_quantity + 1) > $available_stock) {

                $response['message'] =
                    "Only $available_stock item(s) of size $size available in stock.";
            } else {

                /*
                 * If the SAME product AND SAME SIZE already
                 * exists in the cart, increase quantity.
                 */
                if (isset($_SESSION['cart'][$cartKey])) {

                    $_SESSION['cart'][$cartKey]['qty']++;
                } else {

                    /*
                     * Otherwise create a completely new
                     * cart item.
                     */
                    $_SESSION['cart'][$cartKey] = [
                        'product_id' => $id,
                        'qty'        => 1,
                        'size'       => $size
                    ];
                }

                $response['success'] = true;
                $response['message'] = 'Product added to cart.';
            }
        }


        /*
         * ==========================================================
         * UPDATE QUANTITY
         * ==========================================================
         */ elseif ($action === 'update' && isset($data['quantity'])) {

            /*
             * The frontend sends the cart key so we know
             * exactly which product + size to update.
             */
            $requested_quantity = intval($data['quantity']);

            $requested_cart_key = isset($data['cart_key'])
                ? $data['cart_key']
                : $cartKey;

            if (isset($_SESSION['cart'][$requested_cart_key])) {

                /*
                 * Remove item when quantity becomes 0.
                 */
                if ($requested_quantity <= 0) {

                    unset($_SESSION['cart'][$requested_cart_key]);

                    $response['success'] = true;
                    $response['message'] =
                        'Product removed from cart.';
                } else {

                    $cart_item =
                        $_SESSION['cart'][$requested_cart_key];

                    $cart_product_id =
                        intval($cart_item['product_id']);

                    $cart_size =
                        isset($cart_item['size'])
                        ? trim($cart_item['size'])
                        : 'Standard';

                    $escaped_cart_size =
                        mysqli_real_escape_string(
                            $conn,
                            $cart_size
                        );

                    /*
                     * Get current stock for THIS SIZE.
                     */
                    $stock_query = "
                        SELECT quantity
                        FROM product_stock
                        WHERE product_id = $cart_product_id
                        AND size_name = '$escaped_cart_size'
                        LIMIT 1
                    ";

                    $stock_result =
                        mysqli_query($conn, $stock_query);

                    $stock_row = $stock_result
                        ? mysqli_fetch_assoc($stock_result)
                        : null;

                    $available_stock = $stock_row
                        ? intval($stock_row['quantity'])
                        : 0;

                    /*
                     * Size no longer exists / is out of stock.
                     */
                    if ($available_stock <= 0) {

                        unset(
                            $_SESSION['cart'][$requested_cart_key]
                        );

                        $response['success'] = true;
                        $response['message'] =
                            'This size is no longer available.';

                        /*
                     * Requested quantity exceeds stock.
                     */
                    } elseif ($requested_quantity > $available_stock) {

                        $_SESSION['cart'][$requested_cart_key]['qty'] =
                            $available_stock;

                        $response['success'] = false;
                        $response['quantity'] = $available_stock;

                        $response['message'] =
                            "Only $available_stock item(s) of size $cart_size available in stock.";
                    } else {

                        $_SESSION['cart'][$requested_cart_key]['qty'] =
                            $requested_quantity;

                        $response['success'] = true;
                        $response['quantity'] =
                            $requested_quantity;

                        $response['message'] =
                            'Cart quantity updated.';
                    }
                }
            } else {

                $response['message'] =
                    'Product is not in the cart.';
            }
        }


        /*
         * ==========================================================
         * REMOVE PRODUCT
         * ==========================================================
         */ elseif ($action === 'remove') {

            $requested_cart_key = isset($data['cart_key'])
                ? $data['cart_key']
                : $cartKey;

            if (isset($_SESSION['cart'][$requested_cart_key])) {

                unset($_SESSION['cart'][$requested_cart_key]);

                $response['success'] = true;
                $response['message'] =
                    'Product removed from cart.';
            } else {

                $response['message'] =
                    'Product is not in the cart.';
            }
        }
    }
}


/*
 * ==============================================================
 * CALCULATE TOTAL CART ITEMS
 * ==============================================================
 */

$total_items = 0;

foreach ($_SESSION['cart'] as $item) {

    if (is_array($item)) {

        $total_items += intval($item['qty']);
    } else {

        $total_items += intval($item);
    }
}

$response['cart_count'] = $total_items;


/*
 * Return JSON response.
 */

header('Content-Type: application/json; charset=utf-8');

echo json_encode($response);

exit();
