<?php

namespace regenix\modules;

use regenix\Core;
use regenix\Project;
use regenix\lang\ClassScanner;
use regenix\mvc\route\Router;
use regenix\lang\CoreException;
use regenix\lang\File;

abstract class AbstractModule {

    const type = __CLASS__;
    
    public $uid;
    public $version;

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

    public static function getCurrent(){
        $tmp = explode('\\', get_called_class(), 3);
        return self::$modules[ $tmp[1] ];
    }

    /**
     * get route file
     * @return \regenix\lang\File
     */
    final public function getRouteFile(){
        return new File( $this->getPath() . 'conf/route' );
    }
    
    /**
     * get module path
     * @return string
     */
    final public function getPath(){
        return ROOT . 'modules/' . $this->uid . '~' . $this->version . '/';
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
     * @throws static
     * @return boolean
     */
    public static function register($moduleName, $version){
        if ( self::$modules[ $moduleName ] )
            return false;

        ClassScanner::addClassRelativePath('modules/' . $moduleName . '~' . $version);

        $bootstrapName = '\\modules\\' . $moduleName . '\\Module';
        if (!class_exists($bootstrapName)){
            unset(self::$modules[ $moduleName ]);
            throw CoreException::formated('Unload bootstrap `%s` class of `%s` module', $bootstrapName, $moduleName . '~' . $version);
        }

        $module = new $bootstrapName();
        $module->uid     = $moduleName;
        $module->version = $version;

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

