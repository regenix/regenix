<?php

namespace framework\mvc\template;

abstract class TemplateFunctions {

    const type = __CLASS__;

    public static function getModule(){ return null; }
    

    protected static function test($args){
        // nop
    }
}