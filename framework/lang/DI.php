<?php
namespace regenix\lang;

use regenix\mvc\Annotations;

/**
 * Class DI - Dependency Injection Container
 * @package regenix\lang
 */
final class DI {

    private static $reflections = array();
    private static $metaInfo = array();
    private static $singletons = array();

    private static $injectProperties = array();
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

    private static function getInjectProperties(\ReflectionClass $class){
        if (($meta = self::$injectProperties[$class->getName()]) !== null){
            return $meta;
        }

        $meta = array();
        foreach($class->getProperties() as $property){
            $info = Annotations::getPropertyAnnotation($property);
            if ($info->has('inject')){
                $type = $info->get('var');
                if (!$type || !$type->getDefault())
                    throw new DependencyInjectionException("Unknown type for injection, not found @var annotation");

                $property->setAccessible(true);
                $meta[$property->getName()] = array('property' => $property, 'type' => $type->getDefault());
            }
        }

        return self::$injectProperties[$class->getName()] = $meta;
    }

    private static function getMetaInfo($class){
        if ($meta = self::$metaInfo[$class]['#'])
            return $meta;

        return self::$metaInfo[$class]['#'] = Annotations::getClassAnnotation($class);
    }

    private static function validateDI($interface, $implement){
        $meta = ClassScanner::find($interface);
        if (!$meta)
            throw new ClassNotFoundException($interface);

        if (!($info = ClassScanner::find($implement)))
            throw new ClassNotFoundException($implement);

        if (!$meta->isParentOf($implement)){
            throw new DependencyInjectionException('"%s" class should be implemented or inherited by "%s"', $implement, $interface);
        }

        if ($info->isAbstract() || $info->isInterface()){
            throw new DependencyInjectionException('"%s" cannot be an abstract class or interface');
        }
    }

    private static function _getInstance($class){
        if ($class[0] === '\\') $class = substr($class, 1);

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

                        if (REGENIX_IS_DEV)
                            self::validateDI($class, $newClass);

                        $class = $newClass;
                        break;
                    }
                }
            }
        }

        $reflection  = self::getReflection($class);
        $constructor = $reflection->getConstructor();

        $args = array();
        if ($constructor){
            foreach($constructor->getParameters() as $parameter){
                $class = $parameter->getClass();
                if ($class){
                    $args[] = self::getInstance($class->getName());
                } else {
                    $args[] = null;
                }
            }
            $object = $reflection->newInstanceWithoutConstructor();
        } else {
            $object = $reflection->newInstance();
        }

        foreach(self::getInjectProperties($reflection) as $meta){
            /** @var $property \ReflectionProperty */
            $property = $meta['property'];
            $property->setValue($object, self::getInstance($meta['type']));
        }

        if ($constructor)
            $constructor->invokeArgs($object, $args);

        return $object;
    }

    public static function getInstance($class){
        $class     = str_replace('.', '\\', $class);
        $annotations = self::getMetaInfo($class);
        $singleton = ($singletonInstance = self::$singletons[$class]) || $annotations->has('singleton');

        if ($singleton){
            if (!$singletonInstance){
                $singletonInstance = self::$singletons[$class];
            }

            if (!$singletonInstance)
                return self::$singletons[$class] = self::_getInstance($class);
            else
                return $singletonInstance;
        } else {
            return self::_getInstance($class);
        }
    }

    /**
     * @param $interfaceNamespace
     * @param $implementNamespace
     */
    public static function bindNamespaceTo($interfaceNamespace, $implementNamespace){
        $interfaceNamespace = str_replace('.', '\\', $interfaceNamespace);
        $implementNamespace = str_replace('.', '\\', $implementNamespace);

        if ($interfaceNamespace[0] === '\\')
            $interfaceNamespace = substr($interfaceNamespace, 1);

        if ($implementNamespace[0] === '\\')
            $implementNamespace = substr($implementNamespace, 1);

        self::$namespaceBinds[$interfaceNamespace] = $implementNamespace;
        self::$cacheNamespaceBinds = array();
    }

    /**
     * @param $interface
     * @param $class
     * @throws DependencyInjectionException
     * @throws ClassNotFoundException
     */
    public static function bindTo($interface, $class){
        $interface = str_replace('.', '\\', $interface);
        $class     = str_replace('.', '\\', $class);

        if (REGENIX_IS_DEV)
            self::validateDI($interface, $class);

        self::$binds[ $interface ] = $class;
    }
}

    {
        Annotations::registerAnnotation('singleton', array('any' => true), 'class');
        Annotations::registerAnnotation('inject', array('any' => true), array('property', 'method'));
    }

class DependencyInjectionException extends CoreException {}