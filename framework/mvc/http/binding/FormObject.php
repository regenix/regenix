<?php
namespace regenix\mvc\http\binding;

use regenix\lang\ArrayTyped;
use regenix\lang\CoreException;
use regenix\lang\Injectable;
use regenix\mvc\binding\BindValue;
use regenix\mvc\binding\Binder;
use regenix\mvc\http\RequestBody;

class FormObject extends ArrayTyped implements
    Injectable, BindValue {

    const AS_TYPE = 'array';

    const NOT_PRESENT_VALUE = "\1\1\1\2\2\3";
    const NULL_VALUE = "\1null";

    private $present = true;

    /**
     * if this object isset in Json
     * @return bool
     */
    public function isPresent(){
        return $this->present;
    }

    /**
     * @return bool
     */
    public function isInteger(){
        return is_int($this->data);
    }

    /**
     * @return bool
     */
    public function isString(){
        return is_string($this->data);
    }

    /**
     * @return bool
     */
    public function isDouble(){
        return is_double($this->data);
    }

    /**
     * @return bool
     */
    public function isBoolean(){
        return is_bool($this->data);
    }

    /**
     * @return bool
     */
    public function isObject(){
        return is_array($this->data);
    }

    /**
     * @return bool
     */
    public function isNull(){
        return $this->data === null;
    }

    /**
     * @return mixed
     */
    public function getValue(){
        return $this->data;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasKey($name){
        return array_key_exists($name, $this->data);
    }

    /**
     * @param string $json
     * @param null $name
     * @throws \regenix\lang\CoreException
     * @return null
     */
    public function onBindValue($json, $name = null) {
        if ($json === self::NOT_PRESENT_VALUE){
            $this->present = false;
            $json = null;
        }

        $binder = Binder::getInstance();
        if ($json === null){
            $body = RequestBody::getInstance();
            switch(static::AS_TYPE){
                case 'json': $json = $body->asJson(); break;
                case 'array': $json = $body->asArray(); break;
                default:
                    throw new CoreException('Unknown type "%"', static::AS_TYPE);
            }
            if ($json === null || (is_array($json) && !$json))
                $this->present = false;
        }

        if ($json === self::NULL_VALUE)
            $json = null;

        $this->data = $json;

        $class = new \ReflectionClass($this);
        $filter = \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED;
        foreach($class->getProperties($filter) as $property){
            if ($property->getDeclaringClass()->getName() === ArrayTyped::type)
                continue;

            $property->setAccessible(true);
            $name = $property->getName();
            $value = $json[$name];

            $setter = 'set' . $name;
            if ($class->hasMethod($setter)){
                if (array_key_exists($name, $json))
                    $binder->callMethod($this, $class->getMethod($setter), array(
                        $value === null ? self::NULL_VALUE : $value
                    ));
                else
                    $binder->callMethod($this, $class->getMethod($setter), array(self::NOT_PRESENT_VALUE));
            } else {
                $property->setValue($this, $value);
            }
        }
    }
}
