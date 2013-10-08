<?php
namespace regenix\validation\results;

use regenix\lang\StrictObject;
use regenix\i18n\I18n;

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
