<?php

namespace regenix\mvc\controllers;

use regenix\exceptions\HttpException;

class RESTException extends HttpException {

    public function __construct($message = ''){
        parent::__construct(HttpException::E_BAD_REQUEST, $message);
    }
}
