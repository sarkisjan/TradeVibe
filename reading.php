<?php
session_start();
require_once "includes/autoloader.php";
require_once "classes/Currency.php";

$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'user';
$user_id   = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$currency  = isset($_SESSION['currency']) ? $_SESSION['currency'] : 'USD';

// ИНСТАНТЕН OOP ПОВИК: Сè што ни треба сега се само овие две чисти линии!
$process = new Process();
$products = $process->getAllProducts($user_role, $user_id);

// Динамична конверзија на валутите и форматирање на приказните цени
foreach ($products as $key => $product) {
    $clean_price = str_replace(['$', ' ', 'den', 'EUR', '€'], '', $product['price']);
    $raw_price = floatval($clean_price);

    $discount = intval($product['discount']);
    $currency_symbol = Currency::getSymbol($currency);

    $converted_original = Currency::convert($raw_price, $currency);
    $products[$key]['raw_price_converted'] = $converted_original;

    if ($discount > 0) {
        $discounted_price = $raw_price - ($raw_price * ($discount / 100));
        $converted_discounted = Currency::convert($discounted_price, $currency);

        $products[$key]['display_price'] = '
            <span style="text-decoration: line-through; color: #718096; font-size: 14px; font-weight: normal; margin-right: 8px;">' . number_format($converted_original, 2) . $currency_symbol . '</span>
            <span style="color: #ef4444; font-weight: 700;">' . number_format($converted_discounted, 2) . $currency_symbol . '</span>';

        $products[$key]['raw_price_converted'] = $converted_discounted;
    } else {
        $products[$key]['display_price'] = number_format($converted_original, 2) . $currency_symbol;
    }

    // Обезбедуваме полињата за залиха уредно да се спакуваат во финалниот JSON пакет
    $products[$key]['total_qty'] = intval($product['total_qty']);
    $products[$key]['stock_summary'] = $product['stock_summary'] ? $product['stock_summary'] : '';
}

// Return the products as a JSON response with strict encoding safety boundaries
header('Content-Type: application/json; charset=utf-8');

// UTF-8 ESCAPE PARSER: Forces the JSON compiler to substitute invalid string characters instead of crashing
$json_output_stream = json_encode($products, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

if ($json_output_stream === false) {
    // Ultimate fallback matrix log system: Outputs error footprint safely to prevent blank pages
    echo json_encode([["id" => "0", "name" => "JSON Encoding Layer Error Encounted: " . json_last_error_msg(), "price" => "0"]]);
} else {
    echo $json_output_stream;
}
exit();
