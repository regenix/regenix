<?php

namespace framework\modules;

use framework\mvc\route\Router;
use framework\exceptions\CoreException;
use framework\io\File;

abstract class AbstractModule {
    
    
    protected $uid;


    static $modules = array();

    // abstract
    abstract public function getName();
    public function getDescription(){ return null; }
    
    
    // on route reload
    public function onRoute(Router $router){}
    

    /**
     * get route file
     * @return \framework\io\File
     */
    final public function getRouteFile(){
        
        return new File( $this->getPath() . 'conf/route' );
    }
    
    /**
     * get module path
     * @return string
     */
    final public function getPath(){
        
        return 'modules/' . $this->uid . '/';
    }


    // statics
    
    /**
     * register module by name, all modules in module directory
     * @param string $moduleName
     * @return boolean
     */
    public static function register($moduleName){
        
        if ( self::$modules[ $moduleName ] )
            return false;
        
        $bootstrapName = '\\modules\\' . $moduleName . '\\Module';
        
        try {
            $module = new $bootstrapName();
            $module->uid = $moduleName;
        } catch (framework\exceptions\ClassNotFoundException $e){
            throw CoreException::formated('Unload Module.php class of `%s` module', $module);
        }
        
        self::$modules[ $moduleName ] = $module;
        return true;
    }
}

