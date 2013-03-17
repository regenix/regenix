<?php

namespace framework\lang;

abstract class String {

    const type = __CLASS__;

    public static function format($string){

        $args = func_get_args();
        return vsprintf($string, array_slice($args, 1));
    }
    
    /**
     * return true if sting start with 
     * @param string $string
     * @param string $with
     * @return boolean
     */
    public static function startsWith($string, $with){
        
        return strpos($string, $with) === 0;
    }
    
    /**
     * 
     * @param string $string
     * @param string $with
     * @return boolean
     */
    public static function endsWith($string, $with){

        return strpos($string, $with) === strlen($string) - strlen($with);
    }
}
