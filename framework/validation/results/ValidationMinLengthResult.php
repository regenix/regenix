<?php
namespace regenix\validation\results;

class ValidationMinLengthResult extends ValidationResult {

    private $min;

    public function __construct($min){
        $this->min = (int)$min;
    }

    public function check($value){
        return strlen((string)$value) >= $this->min;
    }

    public function getMessageAttr(){
        return array('param' => $this->min);
    }
}
