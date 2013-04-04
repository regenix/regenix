<?php

namespace framework\di\exceptions;

use framework\exceptions\CoreException;
use framework\lang\String;

/**
 * Class DIBindClassNotFound
 * @package framework\di\exceptions
 * @deprecated
 */
class DIBindClassNotFound extends CoreException {

    const type = __CLASS__;

    public function __construct($class){
        parent::__construct(String::format('Binding for "%s" class not found', $class));
    }
}
