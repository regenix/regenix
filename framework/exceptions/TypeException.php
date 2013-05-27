<?php
namespace regenix\exceptions;

use regenix\lang\CoreException;
use regenix\lang\String;

class TypeException extends CoreException {

    const type = __CLASS__;
    
    public function __construct($name, $type) {
        parent::__construct(String::format('Argument "%s" must be `%s`', $name, $type));
    }
}
