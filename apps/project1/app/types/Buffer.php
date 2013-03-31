<?php

namespace types;

use framework\mvc\RequestBindValue;

class Buffer implements RequestBindValue {

    protected $value;

    /**
     * @param $value string
     * @return null
     */
    public function onBindValue($value){
        $this->value = $value;
    }

    public function __toString(){
        return $this->value;
    }
}