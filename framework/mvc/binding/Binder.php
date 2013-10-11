<?php
namespace regenix\mvc\binding;

use regenix\lang\DI;
use regenix\lang\Injectable;
use regenix\lang\Singleton;
use regenix\lang\StrictObject;
use regenix\mvc\binding\BindValue;
use regenix\mvc\binding\exceptions\BindValueException;
use regenix\mvc\binding\exceptions\BindValueInstanceException;

class Binder extends StrictObject implements Singleton {

    const type = __CLASS__;

    /**
     * @param $object
     * @param \ReflectionMethod $method
     * @param array $defaults
     * @return mixed
     */
    public function callMethod($object, \ReflectionMethod $method, array $defaults = array()){
        $args = array();
        reset($defaults);

        foreach($method->getParameters() as $param){
            $name = $param->getName();
            $class = $param->getClass();
            if ($class){
                $args[$name] = $this->getValue(current($defaults), $class->getName(), $name);
            } else {
                $args[$name] = current($defaults);
            }
            next($defaults);
        }
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }

    /**
     * @param $value string
     * @param $type string
     * @param null $name
     * @throws exceptions\BindValueException
     * @throws exceptions\BindValueInstanceException
     * @return array|bool|float|BindValue|int|string
     */
    public function getValue($value, $type, $name = null){
        switch($type){
            case 'int':
            case 'integer':
            case 'long': {
                return (int)$value;
            } break;

            case 'double':
            case 'float': {
                return (double)$value;
            } break;

            case 'bool':
            case 'boolean': {
                return (boolean)$value;
            } break;

            case 'string':
            case 'str': {
                return (string)$value;
            } break;

            case 'array': {
                return array($value);
            } break;

            default: {
            $type = str_replace('.', '\\', $type);
            if ( class_exists($type) ){
                $implements = class_implements($type);
                if ($implements[Injectable::injectable_type])
                    $instance = DI::getInstance($type);

                if ($implements[BindValue::bindValue_type]){
                    if (!$instance)
                        $instance = new $type;
                    $instance->onBindValue($value, $name);
                    return $instance;
                } elseif ($implements[BindStaticValue::bindStaticValue_type]){
                    $instance = call_user_func(array($type, 'onBindStaticValue'), $value, $name);
                    if ($instance !== null && !($instance instanceof BindStaticValue))
                        throw new BindValueException($value, $type);

                    return $instance;
                }

                if (!$instance)
                    throw new BindValueInstanceException($type);

                return $instance;
            } else
                throw new BindValueException($value, $type);
            }
        }
    }

    /**
     * @return Binder
     */
    public static function getInstance() {
        return DI::getInstance(__CLASS__);
    }
}