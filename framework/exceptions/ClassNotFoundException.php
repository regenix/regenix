<?php
namespace regenix\exceptions;

use regenix\lang\CoreException;
use regenix\lang\String;

/**
 * Class ClassNotFoundException
 * @package regenix\lang
 */
class ClassNotFoundException extends CoreException {

    const type = __CLASS__;

    public function __construct($className){
        parent::__construct( String::format('Class "%s" not found', $className) );
    }
}