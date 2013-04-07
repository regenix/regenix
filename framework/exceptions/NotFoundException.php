<?php
/**
 * Author: Dmitriy Zayceff
 * E-mail: dz@dim-s.net
 * Date: 07.04.13
 */

namespace framework\exceptions;


class NotFoundException extends ResponseException {

    public function __construct($message){
        parent::__construct($message);
    }

    function getStatus() {
        return 404;
    }
}