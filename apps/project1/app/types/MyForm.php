<?php


namespace types;

use framework\mvc\RequestBindParams;

class MyForm extends RequestBindParams {

    const type   = __CLASS__;
    const method = 'GET';
    const prefix = 'form_';

    /** @var Integer */
    public $id;

    /** @var Buffer */
    public $name;


    protected function setId(Integer $id){
        $this->id = $id;
    }

    protected function setName(Buffer $name){
        $this->name = $name;
    }
}