<?php

namespace framework\mvc\results;

class Result extends \Exception {

    const type = __CLASS__;

    private $response;

    public function __construct($response) {
        $this->response = $response;
    }
    
    public function getResponse(){
        return $this->response;
    }
}