<?php
namespace regenix\validation\results;

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
