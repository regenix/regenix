<?php
namespace regenix\validation;

use regenix\exceptions\TypeException;
use regenix\validation\exceptions\ValidationException;
use regenix\validation\results\ValidationCallbackResult;
use regenix\validation\results\ValidationFileMaxSizeResult;
use regenix\validation\results\ValidationFileTypeResult;
use regenix\validation\results\ValidationFilterResult;
use regenix\validation\results\ValidationIsEmptyResult;
use regenix\validation\results\ValidationMatchesResult;
use regenix\validation\results\ValidationMaxLengthResult;
use regenix\validation\results\ValidationMinLengthResult;
use regenix\validation\results\ValidationRequiresResult;
use regenix\validation\results\ValidationResult;

abstract class EntityValidator extends Validator {

    /** @var mixed */
    protected $entity;

    /** @var array */
    protected $map = array();

    public function __construct($entity){
        $this->entity = $entity;
    }

    protected function map($callback){
        if (!is_callable($callback))
            throw new TypeException('$callback', 'Callable');

        $this->map[] = $callback;
        return $this;
    }

    /**
     * get value by name of attribute
     * example: getValue('name'), getValue('address.name')
     * @param $attribute
     * @throws ValidationException
     * @return mixed|null
     */
    protected function getAttribute($attribute){
        if (!$attribute)
            return $this->entity;

        $attribute = str_replace('->', '.', $attribute);
        $attrs     = explode('.', $attribute);

        $obj   = $this->entity;
        $value = null;
        while(list($i, $attribute) = each($attrs)){
            if (is_object($obj)){
                if (property_exists($obj, $attribute))
                    $value = $obj = $obj->{$attribute};
                else
                    throw new ValidationException('`%s` attribute does not exist in the %s class', $attribute, get_class($this->entity));
            } else if (is_array($obj)){
                $value = $obj = $obj[$attribute];
            } else
                throw new ValidationException('`%s` attribute must be an object or array', $attribute);
        }

        foreach($this->map as $callback){
            $value = call_user_func($callback, $value);
        }
        $this->map = array();

        return $value;
    }

    protected function validateAttribute($attribute, $message, ValidationResult $validation){
        $validation->message($message);

        if (!$validation->validate($this->getAttribute($attribute))){
            $this->errors[] = array(
                'attr' => $attribute,
                'validator' => $validation
            );
            $this->__ok = false;
            $this->__lastOk = false;
        } else {
            $this->__lastOk = true;
        }
        return $validation;
    }

    protected function addError($attribute, $message){
        $validation = new ValidationCallbackResult(function(){
            return false;
        });

        $validation->message($message);
        $this->errors[] = array(
            'attr' => $attribute,
            'validator' => $validation
        );
        $this->__ok = false;
        $this->__lastOk = false;
        return $validation;
    }

    protected function isEmptyAttr($attribute){
        return $this->validateAttribute($attribute, 'validation.result.isEmpty', new ValidationIsEmptyResult());
    }

    protected function requiresAttr($attribute){
        return $this->validateAttribute($attribute, 'validation.result.requires', new ValidationRequiresResult());
    }

    protected function minLengthAttr($attribute, $min){
        return $this->validateAttribute($attribute, 'validation.result.minLength', new ValidationMinLengthResult($min));
    }

    protected function maxLengthAttr($attribute, $max){
        return $this->validateAttribute($attribute, 'validation.result.maxLength', new ValidationMaxLengthResult($max));
    }

    protected function maxFileSizeAttr($attribute, $size){
        return $this->validateAttribute($attribute, 'validation.result.maxFileSize', new ValidationFileMaxSizeResult($size));
    }

    protected function isFileTypeAttr($attribute, array $extensions){
        return $this->validateAttribute($attribute, 'validation.result.isFileType', new ValidationFileTypeResult($extensions));
    }

    protected function matchesAttr($attribute, $pattern){
        return $this->validateAttribute($attribute, 'validation.result.matches', new ValidationMatchesResult($pattern));
    }

    protected function checkFilterAttr($attribute, $filter){
        return $this->validateAttribute($attribute, 'validation.result.filter', new ValidationFilterResult($filter));
    }
}
