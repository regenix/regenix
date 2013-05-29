<?php
namespace regenix\validation;

use regenix\lang\CoreException;
use regenix\lang\String;
use regenix\libs\I18n;

class ValidationException extends CoreException {}

abstract class Validator {

    /** @var mixed */
    protected $entity;

    /** @var array */
    protected $errors;

    protected function __construct($entity){
        $this->entity = $entity;
    }

    /**
     * get value by name of attribute
     * example: getValue('name'), getValue('address.name')
     * @param $attribute
     * @throws ValidationException
     * @return
     */
    protected function getAttribute($attribute){
        $attribute = str_replace('->', '.', $attribute);
        $attrs     = explode('.', $attribute);

        $obj   = $this->entity;
        $value = null;
        while(list($i, $attribute) = each($attrs)){
            if (is_object($obj)){
                if (property_exists($obj, $attribute))
                    $value = $obj = $obj->{$attribute};
                else
                    throw new ValidationException('`%s` attribute not exists in class %s', $attribute, get_class($this->entity));
            } else if (is_array($obj)){
                $value = $obj = $obj[$attribute];
            } else
                throw new ValidationException('`%s` attribute must be object or array', $attribute);
        }

        return $value;
    }

    protected function validateAttribute($attribute, $message, ValidationResult $validation){
        $validation->message($message);

        if (!$validation->validate($this->getAttribute($attribute))){
            $this->errors[] = array(
                'attr' => $attribute,
                'validator' => $validation
            );
        }
        return $validation;
    }

    protected function isEmpty($attribute){
        return $this->validateAttribute($attribute, 'validation.result.isEmpty', new ValidationIsEmptyResult());
    }

    protected function requires($attribute){
        return $this->validateAttribute($attribute, 'validation.result.requires', new ValidationRequiresResult());
    }

    protected function minLength($attribute, $min){
        return $this->validateAttribute($attribute, 'validation.result.minLength', new ValidationMinLengthResult($min));
    }

    protected function maxLength($attribute, $max){
        return $this->validateAttribute($attribute, 'validation.result.maxLength', new ValidationMaxLengthResult($max));
    }

    public function clear(){
        $this->errors = array();
    }

    /**
     * @param $object
     * @param bool $method
     * @throws ValidationException
     * @param bool|string $method
     * @return Validator
     * @throws ValidationException
     */
    public static function validate($object, $method = false){
        $self = new static($object);
        $self->clear();

        $methods = array();
        if ($method){
            $method = new \ReflectionMethod($self, (string)$method);
            $method->setAccessible(true);

            $methods = array($method);
        } else {
            $reflection = new \ReflectionClass($self);
            foreach($reflection->getMethods() as $method){
                if (!$method->isStatic() && $method->getDeclaringClass()->getName() === $reflection->getName()){
                    $methods[] = $method;
                    $method->setAccessible(true);
                }
            }
        }

        /** @var $method \ReflectionMethod */
        foreach($methods as $method){
            $method->invoke($self);
        }
        return $self;
    }

    /**
     * @return bool
     */
    public function hasErrors(){
        return sizeof($this->errors) > 0;
    }

    /**
     * @return array
     */
    public function getErrors(){
        $result = array();
        foreach($this->errors as $error){
            $result[] = array(
                'attr' => $error['attr'],
                'message' => $error['validator']->getMessage($error['attr'])
            );
        }
        return $result;
    }
}


abstract class ValidationResult {

    /** @var bool */
    private $ok = false;

    /** @var string */
    private $message = '';

    public function message($message){
        $this->message = $message;
        return $this;
    }

    /**
     * @param $value
     * @return bool
     */
    public function validate($value){
        $result = $this->check($value);
        return $this->ok = !!$result;
    }

    public function isOk(){
        return $this->ok;
    }

    abstract public function check($value);
    public function getMessage($attr){
        return I18n::get($this->message, array('attr' => $attr));
    }
}

class ValidationIsEmptyResult extends ValidationResult {

    public function check($value){
        return !$value;
    }
}

class ValidationRequiresResult extends ValidationResult {

    public function check($value){
        return !!$value;
    }
}

class ValidationMinLengthResult extends ValidationResult {

    private $min;

    public function __construct($min){
        $this->min = (int)$min;
    }

    public function check($value){
        return strlen((string)$value) >= $this->min;
    }
}

class ValidationMaxLengthResult extends ValidationResult {

    private $max;

    public function __construct($max){
        $this->max = (int)$max;
    }

    public function check($value){
        return strlen((string)$value) <= $this->max;
    }
}