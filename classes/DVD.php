<?php
require_once "includes/autoloader.php";

class DVD extends Validation implements Product {  
    
    public function validateForm($post){
        $fields = ['sku', 'name', 'price', 'size'];
        
        // 1. Safe Property Check
        foreach($fields as $field) {
            if(!isset($post->$field) || trim($post->$field) === '') {
                $this->addError($field, "Please, provide " . $field);
            }
        }
        
        // 2. Format & Value Validation (Only run if fields exist)
        if (!isset($this->errors['sku'])) {
            $this->validateSku($post);
        }
        if (!isset($this->errors['name'])) {
            $this->validateName($post);
        }
        if (!isset($this->errors['price'])) {
            $this->validatePrice($post);
        }
        if (!isset($this->errors['size'])) {
            $this->validateSize($post);
        }

        // 3. Always return the errors array (even if empty) so JSON encodes properly
        return $this->errors;
    }
}
?>
