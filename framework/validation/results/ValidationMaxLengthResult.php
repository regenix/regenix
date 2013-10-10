<?php
namespace regenix\validation\results;

class ValidationMaxLengthResult extends ValidationResult {

    private $max;

    public function __construct($max){
        $this->max = (int)$max;
    }

    public function check($value){
        return strlen((string)$value) <= $this->max;
    }

    public function getMessageAttr(){
        return array('param' => $this->max);
    }
}
