<?php
namespace framework;

use framework\exceptions\TypeException;

abstract class SDK {

    private static $handlers = array();

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
     * @param string $trigger
     * @param callable $callback
     * @param bool $prepend
     */
    public static function addHandler($trigger, $callback, $prepend = false){
        if (!self::$handlers[$trigger])
            self::$handlers[$trigger] = array();

        self::setCallable($callback, self::$handlers[$trigger], $prepend);
    }

    /**
     * @param string $name
     * @param array $args
     */
    public static function trigger($name, array $args = array()){
        foreach((array)self::$handlers[$name] as $handle){
            call_user_func_array($handle, $args);
        }
    }

    /**
     * @param string $moduleUID
     * @return bool
     */
    public static function isModuleRegister($moduleUID){
        return (boolean)modules\AbstractModule::$modules[ $moduleUID ];
    }
}
