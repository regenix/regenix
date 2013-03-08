<?php

namespace framework\mvc\results;

class Result extends \Exception {
    
    private $response;

    public function __construct($response) {
        $this->response = $response;
    }
    
    public function getResponse(){
        return $this->response;
    }
}