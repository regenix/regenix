<?php

namespace framework\modules;

use framework\exceptions\ClassNotFoundException;
use framework\mvc\route\Router;
use framework\exceptions\CoreException;
use framework\io\File;

abstract class AbstractModule {

    const type = __CLASS__;
    
    protected $uid;


    static $modules = array();

    // abstract
    abstract public function getName();
    public function getDescription(){ return null; }
    
    
    // on route reload
    public function onRoute(Router $router){}
    

    public static function getCurrent(){
        $tmp = explode('\\', get_called_class(), 3);
        return self::$modules[ $tmp[1] ];
    }

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

    /**
     * @return null|string
     */
    final public function getModelPath(){
        $path = $this->getPath() . 'models/';
        return is_dir($path) ? $path : null;
    }

    /**
     * @return null|string
     */
    final public function getControllerPath(){
        $path = $this->getPath() . 'controllers/';
        return is_dir($path) ? $path : null;
    }


    // statics

    /**
     * register module by name, all modules in module directory
     * @param string $moduleName
     * @throws \framework\exceptions\CoreException
     * @return boolean
     */
    public static function register($moduleName){
        if ( self::$modules[ $moduleName ] )
            return false;
        
        self::$modules[ $moduleName ] = true;
        $bootstrapName = '\\modules\\' . $moduleName . '\\Module';
        
        try {
            $module = new $bootstrapName();
            $module->uid = $moduleName;
            self::$modules[ $moduleName ] = $module;
            
        } catch (ClassNotFoundException $e){
            unset(self::$modules[ $moduleName ]);
            throw CoreException::formated('Unload Module.php class of `%s` module', $module);
        }
       
        return true;
    }
}

