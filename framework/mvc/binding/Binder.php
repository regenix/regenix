<?php
namespace regenix\mvc\binding;

use regenix\lang\StrictObject;
use regenix\mvc\binding\BindValue;
use regenix\mvc\binding\exceptions\BindValueException;
use regenix\mvc\binding\exceptions\BindValueInstanceException;

class Binder extends StrictObject {

    const type = __CLASS__;

    /**
     * @param $value string
     * @param $type string
     * @throws BindValueException
     * @throws BindValueInstanceException
     * @return array|bool|float|\regenix\mvc\RequestBindValue|int|string
     */
    public function getValue($value, $type){
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
                $instance = new $type;
                if ( $instance instanceof BindValue ){
                    $instance->onBindValue($value);
                    return $instance;
                } else
                    throw new BindValueInstanceException($type);
            } else
                throw new BindValueException($value, $type);
            }
        }
    }
}