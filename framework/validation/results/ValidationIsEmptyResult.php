<?php
namespace regenix\validation\results;

class ValidationIsEmptyResult extends ValidationResult {
    public function check($value){
        return !$value;
    }
}
