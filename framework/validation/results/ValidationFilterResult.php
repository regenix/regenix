<?php
namespace regenix\validation\results;

class ValidationFilterResult extends ValidationResult {
    private $filter;

    public function __construct($filter){
        $this->filter = $filter;
    }

    public function check($value){
        return filter_var($value, $this->filter) !== false;
    }
}
