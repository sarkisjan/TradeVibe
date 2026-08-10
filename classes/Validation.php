<?php
 require_once "includes/autoloader.php";
class Validation extends Database {
    public function validateSku($post){
        $val = trim($post->sku);
        $conn = $this->connect();
        $res = mysqli_query($conn, "SELECT * FROM ".$this->tbname." WHERE sku='".$val."'");
        while($row = mysqli_fetch_assoc($res)){
            if (is_array($row) && count($row)>0){
                $this->addError('sku', 'This SKU already exist');
            }
        }

    if(empty($val)){
        $this->addError('sku', 'Please, provide sku');
    }
    
    }
    public function validateName($post){
        $val = trim($post->name);
        if(empty($val)){
            $this->addError('name', 'Please, provide name');
        } 
    }
    public function validatePrice($post){
        $val = trim($post->price);
        if(empty($val)){
            $this->addError('price', 'Please, provide price');
        } 
    }
    public function validateSize($post){
        $val = trim($post->size);
        if(empty($val)){
            $this->addError('size', 'Please, provide size');
        }  
    }
    public function validateWeight($post){
        $val = trim($post->weight);
        if(empty($val)){
            $this->addError('weight', 'Please, provide weight');
        }  
    }
    public function validateHeight($post){
        $val = trim($post->height);
        if(empty($val)){
            $this->addError('height', 'Please, provide height');
        }  
    }
    public function validateWidth($post){
        $val = trim($post->width);
        if(empty($val)){
            $this->addError('width', 'Please, provide width');
        }  
    }
    public function validateLength($post){
        $val = trim($post->length);
        if(empty($val)){
            $this->addError('length', 'Please, provide length');
        } 
    }
    public function addError($key, $val){
        $this->errors[$key] = $val;
    }
               
    }
?>