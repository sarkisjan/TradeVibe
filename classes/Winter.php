<?php
class Winter extends Validation implements Product
{
    public function validateForm($post)
    {


        $hasStockData = false;

        if (isset($post->stockData)) {
            $stockDataRaw = $post->stockData;
            if (!empty($stockDataRaw)) {
                $hasStockData = true;
            }
        } elseif (is_array($post) && isset($post['stockData']) && !empty($post['stockData'])) {
            $hasStockData = true;
        }

        // Return structured subcategory interface message layer if no items are tracked
        if (!$hasStockData) {
            $this->addError('subcategory', "Please select at least one apparel size configuration check-box layout with available inventory stock.");
        }

        // Base parameter mapping validation loop
        $fields = ['sku', 'name', 'price'];

        foreach ($fields as $field) {
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
