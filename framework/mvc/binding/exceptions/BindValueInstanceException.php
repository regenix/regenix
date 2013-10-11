<?php
namespace regenix\mvc\binding\exceptions;

use regenix\lang\CoreException;
use regenix\lang\String;
use regenix\mvc\binding\BindValue;

class BindValueInstanceException extends BindException {

    const type = __CLASS__;

    public function __construct($type){
        parent::__construct(String::format(
            'Bind error: `%s` class must be implements "%s" interface for bind value',
            $type, BindValue::bindValue_type
        ));
    }
}