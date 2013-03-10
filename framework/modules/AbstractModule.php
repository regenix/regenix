<?php

namespace framework\modules;

use framework\mvc\route\Router;
use framework\exceptions\CoreException;

abstract class AbstractModule {
    
    static $instances = array();

    // abstract
    abstract public function getName();
    public function getDescription(){ return null; }
    
    
    // on route reload
    public function onRoute(Router $router){}
    

    // statics
    
    /**
     * register module by name, all modules in module directory
     * @param string $moduleName
     * @return boolean
     */
    public static function register($moduleName){
        
        if ( self::$instances[ $moduleName ] )
            return false;
        
        $bootstrapName = '\\modules\\' . $moduleName . '\\Module';
        
        try {
            $module = new $bootstrapName();
        } catch (framework\exceptions\ClassNotFoundException $e){
            throw CoreException::formated('Unload Module.php class of `%s` module', $module);
        }
        
        self::$instances[ $moduleName ] = $module;
        return true;
    }
}

