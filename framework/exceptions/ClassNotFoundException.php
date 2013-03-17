<?php
namespace framework\exceptions;

use framework\exceptions\CoreException;
use framework\lang\String;

class ClassNotFoundException extends CoreException {

    const type = __CLASS__;

    public function __construct($className){

        parent::__construct( String::format('Class "%s" not found', $className) );
    }
}
