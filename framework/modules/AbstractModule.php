<?php

namespace framework\modules;

use framework\Core;
use framework\exceptions\ClassNotFoundException;
use framework\lang\ClassLoader;
use framework\mvc\route\Router;
use framework\exceptions\CoreException;
use framework\io\File;

abstract class AbstractModule {

    const type = __CLASS__;
    
    protected $uid;


    static $modules = array();

    // abstract
    abstract public function getName();

    /**
     * @return array
     */
    public static function getDeps(){
        return array();
    }

    /**
     * @return array
     */
    public static function getAssetDeps(){
        return array();
    }

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
     * @param $version
     * @throws
     * @return boolean
     */
    public static function register($moduleName, $version){
        if ( self::$modules[ $moduleName ] )
            return false;

        ClassLoader::$modulesLoader->addModule($moduleName, $version);

        self::$modules[ $moduleName ] = true;
        $bootstrapName = '\\modules\\' . $moduleName . '\\Module';
        if (!ClassLoader::load($bootstrapName)){
            unset(self::$modules[ $moduleName ]);
            throw CoreException::formated('Unload bootstrap `%s` class of `%s` module', $bootstrapName, $moduleName . '~' . $version);
        }

        /** @var $module AbstractModule */
        $deps = $bootstrapName::getDeps();
        foreach((array)$deps as $name => $ver){
            self::register($name, $ver);
        }

        $module = new $bootstrapName();
        $module->uid = $moduleName;

        self::$modules[ $moduleName ] = $module;

        return true;
    }


    /**
     * @return array
     */
    public static function getAllModules(){
        $result = array();
        $dirs   = scandir(ROOT . 'modules/');
        foreach((array)$dirs as $dir){
            $dir = basename($dir);
            if ($dir){
                $dir = explode('~', $dir);
                if ($dir[1]){
                    $result[$dir[0]][] = $dir[1];
                }
            }
        }

        return $result;
    }
}

