<?php
class Equipment extends Validation implements Product
{
    public function validateForm($post)
    {

        // Mandated specifications for Workout Equipment (Core details + 3 Dimensions)
        $fields = ['sku', 'name', 'price', 'height', 'width', 'length'];

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
