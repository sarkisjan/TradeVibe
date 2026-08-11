<?php
class Currency
{

    private static $rates = [
        'USD' => 1.0,
        'EUR' => 0.92,  // 1 USD = 0.92 EUR
        'MKD' => 56.50  // 1 USD = 56.50 MKD
    ];

    public static function convert($priceInUSD, $targetCurrency)
    {
        if (!array_key_exists($targetCurrency, self::$rates)) {
            $targetCurrency = 'USD';
        }
        return $priceInUSD * self::$rates[$targetCurrency];
    }

    public static function getSymbol($currency)
    {
        switch ($currency) {
            case 'EUR':
                return '€';
            case 'MKD':
                return ' ден';
            case 'USD':
            default:
                return ' $';
        }
    }
}
