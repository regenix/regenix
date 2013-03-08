<?php
namespace framework\exceptions;

use framework\exceptions\CoreException;
use framework\utils\StringUtils;

class ClassNotFoundException extends CoreException {

    public function __construct($className){

        parent::__construct( StringUtils::format('Class "%s" not found', $className) );
    }
}
