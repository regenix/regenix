<?php
namespace framework;

use framework\exceptions\CoreException;
use framework\exceptions\TypeException;

abstract class SDK {

    private static $types = array();
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
     * @throws static
     */
    public static function addHandler($trigger, $callback, $prepend = false){
        if (IS_DEV && !self::$types[$trigger])
            throw CoreException::formated('Trigger type `%s` is not registered', $trigger);

        if (!self::$handlers[$trigger])
            self::$handlers[$trigger] = array();

        self::setCallable($callback, self::$handlers[$trigger], $prepend);
    }

    /**
     * @param string $name
     * @param array $args
     * @throws exceptions\CoreException
     */
    public static function trigger($name, array $args = array()){
        if (IS_DEV && !self::$types[$name])
            throw CoreException::formated('Trigger type `%s` is not registered', $name);

        foreach((array)self::$handlers[$name] as $handle){
            call_user_func_array($handle, $args);
        }
    }

    /**
     * @param string $name
     */
    public static function registerTrigger($name){
        self::$types[$name] = true;
    }

    /**
     * @param string $name
     */
    public static function unregisterTrigger($name){
        unset(self::$types[$name]);
    }

    /**
     * @param string $moduleUID
     * @return bool
     */
    public static function isModuleRegister($moduleUID){
        return (boolean)modules\AbstractModule::$modules[ $moduleUID ];
    }
}
