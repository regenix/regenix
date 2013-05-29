<?php
namespace regenix\exceptions;

use regenix\Application;
use regenix\lang\CoreException;
use regenix\lang\String;

class CoreStrictException extends CoreException {

    const type = __CLASS__;

    public function __construct($message){
        parent::__construct($message . ', {app.mode.strict = on}');
    }
    
    public static function formated($message){
        $args = array();
        if (func_num_args() > 1){
            $args = array_slice(func_get_args(), 1);
        }
        
        return new CoreStrictException(vsprintf($message, $args));
    }
}
