<?php
session_start();
require_once "includes/autoloader.php";
require_once "classes/Currency.php";

// Get the current user's role, ID, and selected currency
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'user';
$user_id   = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$currency  = isset($_SESSION['currency']) ? $_SESSION['currency'] : 'USD';

// Create the Process object and get all available products
$process = new Process();
$products = $process->getAllProducts($user_role, $user_id);

// Convert prices to the selected currency and prepare them for display
foreach ($products as $key => $product) {
    // Remove currency symbols and other characters from the stored price
    $clean_price = str_replace(['$', ' ', 'den', 'EUR', '€'], '', $product['price']);
    $raw_price = floatval($clean_price);

    $discount = intval($product['discount']);
    $currency_symbol = Currency::getSymbol($currency);

    // Convert the original price to the selected currency
    $converted_original = Currency::convert($raw_price, $currency);
    $products[$key]['raw_price_converted'] = $converted_original;

    // Calculate and display the discounted price if a discount exists
    if ($discount > 0) {
        $discounted_price = $raw_price - ($raw_price * ($discount / 100));
        $converted_discounted = Currency::convert($discounted_price, $currency);

        // Show the original price with a line through it and the discounted price
        $products[$key]['display_price'] = '
            <span style="text-decoration: line-through; color: #718096; font-size: 14px; font-weight: normal; margin-right: 8px;">' . number_format($converted_original, 2) . $currency_symbol . '</span>
            <span style="color: #ef4444; font-weight: 700;">' . number_format($converted_discounted, 2) . $currency_symbol . '</span>';

        // Use the discounted price for calculations
        $products[$key]['raw_price_converted'] = $converted_discounted;
    } else {
        // Display the original price when there is no discount
        $products[$key]['display_price'] = number_format($converted_original, 2) . $currency_symbol;
    }

    // Make sure stock values are returned in the correct format
    $products[$key]['total_qty'] = intval($product['total_qty']);
    $products[$key]['stock_summary'] = $product['stock_summary'] ? $product['stock_summary'] : '';
}

// Return the products as a JSON response
header('Content-Type: application/json');
echo json_encode($products);
exit();
