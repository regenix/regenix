<?php
namespace regenix\validation\results;

class ValidationRequiresResult extends ValidationResult {
    public function check($value){
        return !!$value;
    }
}
