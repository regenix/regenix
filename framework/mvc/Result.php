<?php
namespace regenix\mvc;

use regenix\mvc\http\Response;

class Result extends \Exception {

    const type = __CLASS__;

    /** @var Response */
    private $response;

    public function __construct(Response $response) {
        $this->response = $response;
    }

    public function getResponse(){
        return $this->response;
    }
}