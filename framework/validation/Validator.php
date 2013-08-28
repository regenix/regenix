<?php
namespace regenix\validation;

use regenix\exceptions\TypeException;
use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\lang\StrictObject;
use regenix\lang\String;
use regenix\libs\I18n;
use regenix\mvc\UploadFile;

class ValidationException extends CoreException {}

abstract class Validator {

    /** @var array */
    protected $errors;

    /** @var boolean */
    protected $__ok = true;

    /** @var bool */
    protected $__lastOk = true;

    /**
     * @param $value
     * @param $message
     * @param ValidationResult $validation
     * @return ValidationResult
     */
    protected function validateValue($value, $message, ValidationResult $validation){
        $validation->message($message);

        if (!$validation->validate($value)){
            $this->errors[] = array(
                'attr' => null,
                'value' => $value,
                'validator' => $validation
            );
            $this->__ok = false;
            $this->__lastOk = false;
        } else {
            $this->__lastOk = true;
        }
        return $validation;
    }

    public function clear(){
        $this->errors = array();
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
                'value' => $error['value'],
                'message' => $error['validator']->getMessage($error['attr'])
            );
        }
        return $result;
    }

    /**
     * @param bool $method
     * @throws ValidationException
     * @param bool|string $method
     * @return Validator
     */
    public function validate($method = false){
        $this->clear();

        $methods = array();
        if ($method){
            $method = new \ReflectionMethod($this, (string)$method);
            $method->setAccessible(true);

            $methods = array($method);
        } else {
            $reflection = new \ReflectionClass($this);
            foreach($reflection->getMethods() as $method){
                if (!$method->isStatic() && $method->getDeclaringClass()->getName() === $reflection->getName()){
                    $methods[] = $method;
                    $method->setAccessible(true);
                }
            }
        }

        /** @var $method \ReflectionMethod */
        foreach($methods as $method){
            $method->invoke($this);
        }
        return $this;
    }

    protected function isOk(){
        return $this->__ok;
    }

    protected function isLastOk(){
        return $this->__lastOk;
    }

    protected function isEmpty($value){
        return $this->validateValue($value, 'validation.result.isEmpty', new ValidationIsEmptyResult());
    }

    protected function requires($value){
        return $this->validateValue($value, 'validation.result.requires', new ValidationRequiresResult());
    }

    protected function minLength($value, $min){
        return $this->validateValue($value, 'validation.result.minLength', new ValidationMinLengthResult($min));
    }

    protected function maxLength($value, $max){
        return $this->validateValue($value, 'validation.result.maxLength', new ValidationMaxLengthResult($max));
    }

    protected function maxFileSize($value, $size){
        return $this->validateValue($value, 'validation.result.maxFileSize', new ValidationFileMaxSizeResult($size));
    }

    protected function isFileType($value, array $extensions){
        return $this->validateValue($value, 'validation.result.isFileType', new ValidationFileTypeResult($extensions));
    }

    protected function matches($value, $pattern){
        return $this->validateValue($value, 'validation.result.matches', new ValidationMatchesResult($pattern));
    }

    protected function checkFilter($value, $filter){
        return $this->validateValue($value, 'validation.result.filter', new ValidationFilterResult($filter));
    }
}

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


abstract class ValidationResult extends StrictObject {

    /** @var bool */
    private $ok = false;

    /** @var string */
    private $message = '';

    /** @var array */
    private $messageArgs;

    /** @var string */
    private $attr = '';

    public function message($message, array $args = array()){
        $this->message = $message;
        $this->messageArgs = $args;
        return $this;
    }

    /**
     * redefines the name of an attribute
     * @param $name
     * @return $this
     */
    public function attr($name){
        $this->attr = $name;
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

    /**
     * returns true if validation is ok
     * @return bool
     */
    public function isOk(){
        return $this->ok;
    }

    /**
     * returns additional message args
     * @return array
     */
    protected function getMessageAttr(){
        return array();
    }

    /**
     * main method for checking value
     * @param $value
     * @return mixed
     */
    abstract public function check($value);

    /**
     * return an end formatted message of validation
     * @param $attr
     * @return string
     */
    public function getMessage($attr){
        return I18n::get(
            $this->message,
            array_merge(
                array('attr' => $this->attr ? $this->attr : $attr),
                $this->getMessageAttr(),
                $this->messageArgs
            )
        );
    }
}

class ValidationCallbackResult extends ValidationResult {

    /** @var callable */
    private $callback;

    public function __construct($callback){
        if (REGENIX_IS_DEV && !is_callable($callback))
            throw new TypeException('$callback', 'callable');

        $this->callback = $callback;
    }

    public function check($value){
        return call_user_func($this->callback, $value);
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

    public function getMessageAttr(){
        return array('param' => $this->min);
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

    public function getMessageAttr(){
        return array('param' => $this->max);
    }
}

class ValidationMatchesResult extends ValidationResult {

    private $pattern;

    public function __construct($pattern){
        $this->pattern = $pattern;
    }

    public function check($value){
        return preg_match($this->pattern, $value);
    }

    public function getMessageAttr(){
        return array('param' => $this->pattern);
    }
}

class ValidationFilterResult extends ValidationResult {

    private $filter;

    public function __construct($filter){
        $this->filter = $filter;
    }

    public function check($value){
        return filter_var($value, $this->filter) !== false;
    }
}

class ValidationFileMaxSizeResult extends ValidationResult {

    private $size;

    public function __construct($size){
        $this->size = $size;
    }

    public function check($value){
        $file = $value;
        if (!($file instanceof File))
            return false;

        return $file->length() <= $this->size;
    }

    public function getMessageAttr(){
        return array('param' => $this->size);
    }
}

class ValidationFileTypeResult extends ValidationResult {

    private $types;

    public function __construct(array $types){
        $this->types = array_map('strtolower', $types);
    }

    public function check($value){
        $file = $value;
        if (!($file instanceof File))
            return false;

        $ext = strtolower($file->getExtension());
        if ($file instanceof UploadFile){
            $ext = strtolower($file->getMimeExtension());
        }

        return in_array($ext, $this->types, true);
    }

    public function getMessageAttr(){
        return array('param' => implode(', ', $this->types));
    }
}