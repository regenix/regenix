<?php
namespace types;

use framework\mvc\RequestBindValue;

class Integer implements RequestBindValue {

    const type = __CLASS__;

    public $value;

    /**
     * @param $value string
     * @return null
     */
    public function onBindValue($value){
        $this->value = (int)$value;
    }

    public function __toString(){
        return (string)$this->value;
    }
}