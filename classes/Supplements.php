<?php
class Supplements extends Validation implements Product
{
    public function validateForm($post)
    {


        $fields = ['sku', 'name', 'price', 'weight'];

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
