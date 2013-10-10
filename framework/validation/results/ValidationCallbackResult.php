<?php
namespace regenix\validation\results;

use regenix\exceptions\TypeException;

class ValidationCallbackResult extends ValidationResult {

    /** @var callable */
    private $callback;

    public function __construct($callback){
        if (REGENIX_IS_DEV === true && !is_callable($callback))
            throw new TypeException('$callback', 'callable');

        $this->callback = $callback;
    }

    public function check($value){
        return call_user_func($this->callback, $value);
    }
}
