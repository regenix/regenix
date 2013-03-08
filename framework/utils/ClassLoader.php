<?php
namespace framework\utils;
use framework\exceptions\ClassNotFoundException;

require 'framework\utils\StringUtils.php';
require 'framework\exceptions\CoreException.php';
require 'framework\exceptions\ClassNotFoundException.php';


class ClassLoader {

    private $classPaths = array();
    private $namespaces = array();
    
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
            
            self::$classCount += 1;
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
                if (file_exists($file))
                    return $file;
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

$loader = new ClassLoader();
$loader->addNamespace('framework', '');
$loader->register();
ClassLoader::$frameworkLoader = $loader;