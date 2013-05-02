<?php
namespace framework\exceptions;

class ForbiddenException extends ResponseException {

    public function __construct($message){
        parent::__construct($message);
    }

    /** @return int */
    function getStatus() {
        return 403;
    }
}