<?php
class Shoes extends Validation implements Product {
    public function validateForm($post) {
        
        // БЕЗБЕДНА ПРОВЕРКА ЗА ЗАЛИХАТА: Проверуваме дали пристигнале штиклирани големини
        $hasStockData = false;
        
        if (isset($post->stockData)) {
            $stockDataRaw = $post->stockData;
            if (!empty($stockDataRaw)) {
                $hasStockData = true;
            }
        } elseif (is_array($post) && isset($post['stockData']) && !empty($post['stockData'])) {
            $hasStockData = true;
        }

        // Ако нема штиклирано ништо, фрли грешка во подкатегоријата
        if (!$hasStockData) {
            $this->addError('subcategory', "Please select at least one footwear size checkbox layout with its available stock input quantity filled.");
        }
        
        // БЕЗБЕДНА ПРОВЕРКА НА ОСНОВНИТЕ ПОЛИЊА
        $fields = ['sku', 'name', 'price'];
        
        foreach($fields as $field) {
            if (isset($post->$field)) {
                if (trim($post->$field) === '') {
                    $this->addError($field, "Please provide the product " . $field);
                }
            } else {
                $this->addError($field, "Please provide the product " . $field);
            }
        }
        
        return $this->errors;
    }
}
?>
