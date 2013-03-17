<?php
namespace framework\mvc;


abstract class CoreBootstrap {

    const type = __CLASS__;

    private static function registerLogger(){

        
    }

    public static function init(){

        self::registerLogger();
    }
}