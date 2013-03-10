<?php
namespace framework\exceptions;

use framework\utils\StringUtils;

class TypeException extends CoreException {
    
    
    public function __construct($name, $type) {
        parent::__construct(StringUtils::format('Argument "%s" must be `%s`', $name, $type));
    }
}
