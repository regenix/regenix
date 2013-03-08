<?php

namespace framework\di\exceptions;

use framework\exceptions\CoreException;
use framework\utils\StringUtils;


class DIBindClassNotFound extends CoreException {

    public function __construct($class){

        parent::__construct(StringUtils::format('Binding for "%s" class not found', $class));
    }
}
