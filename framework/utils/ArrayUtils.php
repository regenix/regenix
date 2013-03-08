<?php

namespace framework\utils;

use framework\exceptions\CoreException;

class ArrayUtils {

    const TYPE_STRING  = 1;
    const TYPE_BOOLEAN = 2;
    const TYPE_NUMBER  = 3;
    const TYPE_DOUBLE  = 4;
    const TYPE_ARRAY   = 5;


    /**
     * 
     * @param string $type
     * @return integer
     */
    private static function getType($type){
        
        switch ($type){
            case 'string':
            case 'str': {
                return self::TYPE_STRING;
            } break;
        
            case 'boolean':
            case 'bool': {
                return self::TYPE_BOOLEAN;
            } break;
        
            case 'integer': 
            case 'int': 
            case 'number': {
                return self::TYPE_NUMBER;
            } break;
        
            case 'float':
            case 'double': {
                return self::TYPE_DOUBLE;
            } break;
        
            case 'array': {
                return self::TYPE_ARRAY;
            } break;
        
            default: {
                throw new CoreException('Unknow type `' . $type . '`');
            }
        }
    }

    private static function typedValue($value, $type){
        
        switch ($type){
            case self::TYPE_STRING: return (string)$value;
            case self::TYPE_BOOLEAN: return (boolean)$value;
            case self::TYPE_NUMBER: return (integer)$value;
            case self::TYPE_DOUBLE: return (double)$value;
            case self::TYPE_ARRAY: return (array)$value;
            default:
                return $value;
        }
    }

    /**
     * 
     * @param array $array
     * @param string $type must be string|boolean|integer|double|array
     */
    public static function typed(array $array, $type){
        
        $type = self::getType( $type );
        
        foreach ($array as &$value){
            $value = self::typedValue($value, $type);
        }
        unset($value);
        return $array;
    }
}

