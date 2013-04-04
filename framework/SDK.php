<?php
namespace framework;

use framework\exceptions\TypeException;

abstract class SDK {
    
    static $beforeHandlers = array();
    static $afterHandlers  = array();
    static $finallyHandlers = array();

    private static function setCallable($callback, array &$to, $prepend = false){
        if ( IS_DEV && !is_callable($callback) ){
            throw new TypeException('callback', 'callable');
        }
        
        if ( $prepend )
            array_unshift($to, $callback);
        else    
            $to[] = $callback;
    }

    /**
     * add callback handle to before request
     * @param callable $callback
     */
    public static function addBeforeRequest($callback){   
        self::setCallable($callback, self::$beforeHandlers, false);
    }
    
    /**
     * add callback handle to after request
     * @param callable $callback
     */
    public static function addAfterRequest($callback){
        self::setCallable($callback, self::$afterHandlers, false);
    }
    
    /**
     * add callback handle to finaly request
     * @param callable $callback
     */
    public static function addFinallyRequest($callback){
        self::setCallable($callback, self::$finallyHandlers, false);
    }


    /**
     * add callback handle to after model class load
     * @param callable $callback
     */
    public static function addAfterModelLoad($callback){
        self::setCallable($callback, self::$modelLoadHandlers, false);
    }
    
    
    public static function doBeforeRequest(mvc\Controller $controller){
        foreach(self::$beforeHandlers as $handle){
            call_user_func($handle, $controller);
        }
    }
    
    public static function doAfterRequest(mvc\Controller $controller){
        foreach(self::$afterHandlers as $handle){
            call_user_func($handle, $controller);
        }
    }

    /**
     * @param mvc\Controller $controller
     */
    public static function doFinallyRequest(mvc\Controller $controller){
        foreach(self::$finallyHandlers as $handle){
            call_user_func($handle, $controller);
        }
    }
    
    public static function isModuleRegister($moduleUID){
        return (boolean)modules\AbstractModule::$modules[ $moduleUID ];
    }
}
