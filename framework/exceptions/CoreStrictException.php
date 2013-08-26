<?php
namespace regenix\exceptions;

use regenix\Application;
use regenix\lang\CoreException;
use regenix\lang\String;

class CoreStrictException extends CoreException {

    const type = __CLASS__;

    public function __construct($message){
        parent::__construct(String::formatArgs($message, array_slice(func_get_args(), 1)) . ', {src.mode.strict = on}');
    }
}
