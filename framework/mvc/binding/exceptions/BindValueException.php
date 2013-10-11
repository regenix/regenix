<?php
namespace regenix\mvc\binding\exceptions;

use regenix\lang\CoreException;
use regenix\lang\String;

class BindValueException extends BindException {

    const type = __CLASS__;

    public function __construct($value, $type){
        parent::__construct(String::format('Can\'t bind `%s` value as `%s` type', (string)$value, $type));
    }
}