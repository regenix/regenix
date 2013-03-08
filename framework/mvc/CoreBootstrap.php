<?php
namespace framework\mvc;


abstract class CoreBootstrap {

    private static function registerLogger(){

        
    }

    public static function init(){

        self::registerLogger();
    }
}