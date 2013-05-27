<?php
namespace framework\exceptions;

use framework\lang\CoreException;
use framework\lang\String;

class TypeException extends CoreException {

    const type = __CLASS__;
    
    public function __construct($name, $type) {
        parent::__construct(String::format('Argument "%s" must be `%s`', $name, $type));
    }
}
