<?php
class Bedding extends Validation implements Product {
    public function validateForm($post) {
        
        // Специфични задолжителни полиња за постелнини (Основни податоци + 3 Димензии)
        $fields = ['sku', 'name', 'price', 'height', 'width', 'length'];
        
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
