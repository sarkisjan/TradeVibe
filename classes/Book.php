<?php
class Book extends Validation implements Product
{
    public function validateForm($post)
    {
        $fields = ['sku', 'name', 'price', 'weight'];
        foreach ($fields as $field) {
            if (!isset($post->$field) || trim($post->$field) === '') {
                $this->addError($field, "Please provide " . $field);
            }
        }
        return $this->errors;
    }
}
