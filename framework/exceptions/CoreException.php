<?php
namespace framework\exceptions;

use framework\utils\StringUtils;

class CoreException extends \Exception {

    public function __construct($message){
        parent::__construct($message);

        // TODO
    }
    
    public static function formated($message){
        
        $args = array();
        if (func_num_args() > 1){
            $args = array_slice(func_get_args(), 1);
        }
        
        return new CoreException(StringUtils::format($message, $args));
    }
}
