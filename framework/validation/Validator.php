<?php
namespace regenix\validation;

use regenix\lang\CoreException;
use regenix\lang\String;
use regenix\validation\exceptions\ValidationException;
use regenix\validation\results\ValidationFileMaxSizeResult;
use regenix\validation\results\ValidationFileTypeResult;
use regenix\validation\results\ValidationFilterResult;
use regenix\validation\results\ValidationIsEmptyResult;
use regenix\validation\results\ValidationMatchesResult;
use regenix\validation\results\ValidationMaxLengthResult;
use regenix\validation\results\ValidationMinLengthResult;
use regenix\validation\results\ValidationRequiresResult;
use regenix\validation\results\ValidationResult;

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

        if ($validation == null || !$validation->validate($value)){
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
        $methods = array();
        if ($method){
            $method = new \ReflectionMethod($this, (string)$method);
            $method->setAccessible(true);

            $methods = array($method);
        } else {
            $reflection = new \ReflectionClass($this);
            foreach($reflection->getMethods() as $method){
                if (!$method->isStatic()
                    && $method->isProtected()
                    && $method->getDeclaringClass()->getName() === $reflection->getName()){
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
