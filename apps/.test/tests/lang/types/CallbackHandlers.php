<?php
namespace tests\lang\types;


class CallbackHandlers {

    const type = __CLASS__;

    public static function foobar($arg){
        return $arg;
    }

    public function foobar2($arg){
        return $arg;
    }
}