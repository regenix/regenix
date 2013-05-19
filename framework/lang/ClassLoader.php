<?php

namespace framework\lang;

use framework\exceptions\ClassNotFoundException;
use framework\SDK;
use framework\exceptions\CoreException;
use framework\lang\String;

require 'framework/lang/String.php';
require 'framework/exceptions/CoreException.php';
require 'framework/exceptions/ClassNotFoundException.php';


class ClassLoader {

    const type = __CLASS__;

    private $classPaths = array();
    private $classLibPaths = array();
    private $namespaces = array();

    /**
     * @var ClassLoader
     */
    public static $frameworkLoader;

    /**
     * @var ModulesClassLoader
     */
    public static $modulesLoader;

    /**
     * @param $fileName string
     * @param $class string
     * @throws \framework\exceptions\CoreException
     */
    protected function checkCaseFilename($fileName, $class){
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);

        if ( !String::endsWith($fileName, $class . '.php') )
            throw CoreException::formated('Unable load `%s`, "%s.php" class file name case sensitive', $class,  $class);
    }

    public function addClassPath($path, $prepend = false) {
        if ($prepend)
            array_unshift( $this->classPaths, $path );
        else
            $this->classPaths[] = $path;
    }

    public function addClassLibPath($path, $prepend = false) {
        if ($prepend)
            array_unshift( $this->classLibPaths, $path );
        else
            $this->classLibPaths[] = $path;
    }

    public function addNamespace($namespace, $path, $prepend = false, $callback = null) {
        if ($prepend) {
            array_shift( $this->namespaces, array('namespace' => $namespace, 'path' => $path, 'callback' => $callback) );
        } else {
            $this->namespaces[] = array('namespace' => $namespace, 'path' => $path, 'callback' => $callback);
        }
    }

    public function loadClass($class) {
        $file = $this->findFile( $class );

        if ($file != null) {
            require $file;

            if (!class_exists( $class, false ) && !interface_exists( $class, false )
                    && !trait_exists($class, false))
                throw new ClassNotFoundException( $class );

            $implements = class_implements($class);
            if ( $implements[IClassInitialization::_type] ){
                $class::initialize();
            }
        }
    }

    public function findFile($class) {
        $class_rev = str_replace( '\\', DIRECTORY_SEPARATOR, $class );
        if (strpos( $class_rev, DIRECTORY_SEPARATOR ) === 0)
            $class_rev = substr( $class, 1 );

        foreach ($this->classPaths as $path) {
            $file = $path . $class_rev . '.php';
            if (file_exists( $file )) {
                return $file;
            }
        }

        foreach ($this->classLibPaths as $path){
            $tmp = explode(DIRECTORY_SEPARATOR, $class_rev, 2);
            $file = $path . $tmp[0] . '/lib/' . $class_rev . '.php';
            if (file_exists( $file ))
                return $file;
        }

        foreach ($this->namespaces as $item) {
            if ( !$item['namespace'] )
                $p = 1;
            else
                $p = strpos( $class, $item['namespace'] );

            if ($p !== false && $p < 2) {
                $file = $item['path'] . $class_rev . '.php';
                if (file_exists( $file )) {

                    if (IS_DEV)
                        $this->checkCaseFilename($file, $class);

                    if ( $item['callback'] )
                        call_user_func($item['callback']);

                    return $file;
                }
            }
        }

        return null;
    }

    public function register($prepend = false) {
        return spl_autoload_register( array($this, 'loadClass'), true, $prepend );
    }

    public function unregister() {
        return spl_autoload_unregister( array($this, 'loadClass') );
    }

    public static function load($class) {
        return class_exists( $class, true );
    }
}

/**
 * Faster optimize classloader for framework classes
 */
class FrameworkClassLoader extends ClassLoader {

    const type = __CLASS__;

    static $classes = array();

    public function loadClass($class) {
        // optimize
        $tmp = explode( '\\', $class, 3 );
        $isModule = false; // $tmp[0] == 'modules';
        $check = $isModule || $tmp[0] == 'framework';

        if ($check) {
            if ( IS_DEV ){
                $this->checkCaseFilename(
                    str_replace( '\\', DIRECTORY_SEPARATOR, $class ) . '.php',
                    $class
                );
            }

            if ($isModule && !SDK::isModuleRegister( $tmp[1] )) {
                throw CoreException::formated(
                        'Unable "%s" class load, module "%s" not registered',
                        $class, $tmp[1]);
            }

            self::$classes[] = $class;
            require str_replace( '\\', DIRECTORY_SEPARATOR, $class ) . '.php';

            $implements = class_implements($class);
            if ( $implements[IClassInitialization::_type] ){
                //call_user_func(array($class, 'initialize'), $class);
                $class::initialize();
            }

            return true;
        }
    }

    public function findFile($class) {
        $file = str_replace( '\\', DIRECTORY_SEPARATOR, $class ) . '.php';
        return file_exists( $file ) ? $file : null;
    }

    public function register($prepend = false) {
        parent::register( $prepend );
        ClassLoader::$frameworkLoader = $this;

        $loader = new ModulesClassLoader();
        $loader->register();
    }
}

class ModulesClassLoader extends ClassLoader {

    private $modules = array();

    public function addModule($name, $version){
        $this->modules[$name] = $version;
    }

    public function findFile($class){
        $mod = explode('\\', $class, 3);
        $mod = $mod[1];

        $ver = $this->modules[$mod];
        if (!$ver)
            return null;

        $file = ROOT . str_replace(
            array(DIRECTORY_SEPARATOR, 'modules/' . $mod . '/'),
            array('/', 'modules/' . $mod . '~' . $ver . '/'),
            $class) . '.php';

        return file_exists($file) ? $file : null;
    }

    public function register($prepend = false) {
        parent::register( $prepend );
        ClassLoader::$modulesLoader = $this;
    }
}

interface IClassInitialization {

    const _type = __CLASS__;

    public static function initialize();
}