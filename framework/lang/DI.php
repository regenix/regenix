<?php
namespace regenix\lang;

use regenix\core\Regenix;
use regenix\exceptions\ClassNotFoundException;

/**
 * Class DI - Dependency Injection Container
 * @package regenix\lang
 */
final class DI {

    const type = __CLASS__;

    private static $reflections = array();
    private static $singletons = array();

    private static $binds = array();
    private static $namespaceBinds = array();
    private static $cacheNamespaceBinds = array();

    private function __construct(){}

    /**
     * @param $class
     * @return \ReflectionClass
     */
    private static function getReflection($class){
        if ($reflection = self::$reflections[$class])
            return $reflection;

        return self::$reflections[$class] = new \ReflectionClass($class);
    }

    private static function validateDI($interface, $implement){
        if (is_callable($implement))
            return;

        if (is_object($implement))
            $implement = get_class($implement);

        $meta = ClassScanner::find($interface);
        if (!$meta)
            throw new ClassNotFoundException($interface);

        if (!($info = ClassScanner::find($implement)))
            throw new ClassNotFoundException($implement);

        /*if (!$meta->isParentOf($implement)){
            throw new DependencyInjectionException('"%s" class should be implemented or inherited by "%s"', $implement,
                $interface);
        }*/

        if ($info->isAbstract() || $info->isInterface()){
            throw new DependencyInjectionException('"%s" cannot be an abstract class or interface');
        }
    }

    private static function _getInstance($class){
        if ($class[0] === '\\') $class = substr($class, 1);

        $interfaceClass = $class;
        if ($bindClass = self::$binds[$class])
            $class = $bindClass;
        else {
            if ($tmp = self::$cacheNamespaceBinds[ $class ]){
                $class = $tmp;
            } else {
                foreach(self::$namespaceBinds as $interfaceNamespace => $implementNamespace){
                    if (String::startsWith($class, $interfaceNamespace)){
                        $newClass = $implementNamespace . substr($class, strlen($interfaceNamespace));
                        self::$cacheNamespaceBinds[$class] = $newClass;

                        if (REGENIX_IS_DEV === true)
                            self::validateDI($class, $newClass);

                        if (self::$singletons[$interfaceNamespace] === true){
                            self::$singletons[$class] = true;
                            return self::getInstance($class);
                        }

                        $class = $newClass;
                        break;
                    }
                }
            }
        }

        if (is_callable($class)){
            $object = call_user_func($class, $interfaceClass);
            if (REGENIX_IS_DEV === true){
                self::validateDI($interfaceClass, $object);
            }
        } else {
            $reflection  = self::getReflection($class);
            $constructor = $reflection->getConstructor();

            $args = array();
            if ($constructor){
                foreach($constructor->getParameters() as $parameter){
                    $cls = $parameter->getClass();
                    if ($cls){
                        $args[] = self::getInstance($cls->getName());
                    } else {
                        $args[] = null;
                    }
                }
                if ($constructor->isPublic()){
                    $object = $reflection->newInstanceArgs($args);
                } else {
                    // for private, protected constructors
                    // PHP 5.3 does not support newInstanceWithoutConstructor :(, this is hack
                    $object = unserialize(sprintf('O:%d:"%s":0:{}', strlen($class), $class));
                    //$object = $reflection->newInstanceWithoutConstructor();
                    $constructor->setAccessible(true);
                    $constructor->invokeArgs($object, $args);
                }
            } else {
                $object = $reflection->newInstance();
            }
        }

        return $object;
    }

    /**
     * @param $class
     * @param bool $createNonSingleton
     * @return null|object
     */
    public static function getInstance($class, $createNonSingleton = true) {
        $class     = str_replace('.', '\\', $class);
        $singleton = self::$singletons[$class];

        if ($singleton === true){
            return self::$singletons[$class] = self::_getInstance($class);
        } else if ($singleton){
            return $singleton;
        } else {
            if ($createNonSingleton){
                $result = self::_getInstance($class);
                if ($result instanceof Singleton)
                    return self::$singletons[$class] = $result;
                return $result;
            } else
                return null;
        }
    }

    /**
     * @param $class
     * @param null $singletonDefault
     * @throws DependencyInjectionException
     * @return null|object
     */
    public static function getSingleton($class, $singletonDefault = null){
        if (self::$binds[$class] && !self::$singletons[$class])
            throw new DependencyInjectionException("DI bind for '%s' class cannot be as singleton", $class);

        if ($singletonDefault){
            $one = self::getInstance($class, false);
            if ($one === null){
                 return self::$singletons[$class] = $singletonDefault;
            }
            return $one;
        } else {
            $one = self::getInstance($class, false);
            if ($one == null){
                return self::$singletons[$class] = self::_getInstance($class);
            } else {

            }
            return $one;
        }
    }

    /**
     * @param $interfaceNamespace
     * @param $implementNamespace
     * @param bool $singleton
     */
    public static function bindNamespaceTo($interfaceNamespace, $implementNamespace,
                                           $singleton = false){
        $interfaceNamespace = str_replace('.', '\\', $interfaceNamespace);
        $implementNamespace = str_replace('.', '\\', $implementNamespace);

        if ($interfaceNamespace[0] === '\\')
            $interfaceNamespace = substr($interfaceNamespace, 1);

        if ($implementNamespace[0] === '\\')
            $implementNamespace = substr($implementNamespace, 1);

        self::$namespaceBinds[$interfaceNamespace] = $implementNamespace;
        self::$cacheNamespaceBinds = array();
        self::$singletons[ $interfaceNamespace ] = $singleton;
    }

    /**
     * @param $interface
     * @param callback|string|null $class
     * @param bool $singleton
     */
    public static function bindTo($interface, $class, $singleton = false){
        $interface = str_replace('.', '\\', $interface);
        if (!is_object($class))
            $class = str_replace('.', '\\', $class);

        if (!$class)
            $class = $interface;

        if (REGENIX_IS_DEV === true)
            self::validateDI($interface, $class);

        self::$binds[ $interface ] = $class;
        if ($singleton){
            self::$singletons[ $interface ] = true;
        }
    }

    /**
     * Bind object as Singleton
     * @param object $object
     * @param null|string $interface class or interface name
     */
    public static function bind($object, $interface = null){
        if ($interface){
            $interface = str_replace('.', '\\', $interface);
            if (REGENIX_IS_DEV === true)
                self::validateDI($interface, get_class($object));

            self::$singletons[$interface] = $object;
        } else
            self::$singletons[get_class($object)] = $object;
    }

    /**
     * @param $class
     * @return mixed
     */
    public static function get($class){
        return self::$singletons[$class];
    }

    public static function clear(){
        self::$singletons = array();
        self::$cacheNamespaceBinds = array();
        //self::$namespaceBinds = array();
        //self::$binds = array();
    }
}

class DependencyInjectionException extends CoreException {}