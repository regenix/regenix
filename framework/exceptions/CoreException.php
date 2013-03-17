<?php
namespace framework\exceptions;

use framework\lang\String;

class CoreException extends \Exception {

    const type = __CLASS__;

    public function __construct($message){
        parent::__construct($message);

        // TODO
    }
    
    public static function formated($message){
        
        $args = array();
        if (func_num_args() > 1){
            $args = array_slice(func_get_args(), 1);
        }
        
        return new CoreException(vsprintf($message, $args));
    }

    public function getSourceLine(){
        return $this->getLine();
    }

    public function getSourceFile(){
        return $this->getFile();
    }
}
