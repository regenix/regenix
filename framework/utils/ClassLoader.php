<?php
namespace framework\utils;
use framework\exceptions\ClassNotFoundException;

require 'framework\utils\StringUtils.php';
require 'framework\exceptions\CoreException.php';
require 'framework\exceptions\ClassNotFoundException.php';

define('CLASS_LOADER_USE_APC', extension_loaded('apc'));

class ClassLoader {

    private $classPaths = array();
    private $namespaces = array();
    
    /** @var \framework\cache\AbstractCache */
    private $cache = null;
    private $isCached = false;


    static $classCount = 0;


    /**
     * @var ClassLoader
     */
    public static $frameworkLoader;


    public function addClassPath($path, $prepend = false){

        if ( $prepend )
            array_unshift($this->classPaths, $path);
        else
            $this->classPaths[] = $path;
    }
    
    public function addNamespace($namespace, $path, $prepend = false){
        
        if ( $prepend ){
            array_shift($this->namespaces, array('namespace' => $namespace, 'path' => $path));
        } else {
            $this->namespaces[] = array('namespace' => $namespace, 'path' => $path);
        }
    }

    public function loadClass($class){
        
        $file = $this->findFile($class);
        
        if ( $file != null ){
            
            require $file;
        
            if ( !class_exists($class, false) )
                throw new ClassNotFoundException($class);
        }
    }
    
    public function findFile($class){
        
        $class_rev  = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        if (strpos( $class_rev, DIRECTORY_SEPARATOR ) === 0 )
            $class_rev = substr ($class, 1);
        
        foreach($this->classPaths as $path){
            
            $file = $path . $class_rev . '.php';
            if ( file_exists($file) ){
                return $file;
            }
        }
        
        foreach($this->namespaces as $item){
            
            $p = strpos( $class, $item['namespace'] );
            if ( $p !== false && $p < 2 ){
                
                $file = $item['path'] . $class_rev . '.php';
                if (file_exists($file)){
                       
                    return $file;
                }
            }
        }
        
        return null;
    }

    public function register($prepend = false){
        return spl_autoload_register(array($this, 'loadClass'), true, $prepend);
    }
    
    public function unregister(){
        return spl_autoload_unregister(array($this, 'loadClass'));
    }
    
    
    public static function load($class){
        class_exists($class, true);
    }
}

/**
 * Faster optimize classloader for framework classes
 */
class FrameworkClassLoader extends ClassLoader {
    
    static $classes = array();
    
    public function loadClass($class){
        
        // optimize
        $check = strpos($class, 'framework\\') === 0 || strpos($class, 'modules\\') === 0;
        
        if ( $check ){
            
            self::$classes[] = $class;
            require str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
            return true;
        }
    }

    public function findFile($class){
        $file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        return file_exists($file) ? $file : null;
    }
    
    public function register($prepend = false) {
        parent::register( $prepend );
        ClassLoader::$frameworkLoader = $this;
    }
}