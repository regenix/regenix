<?php

namespace framework\di {

    /**
     * Class DI
     * @deprecated
     * @package framework\di
     */
    class DI {

        const type = __CLASS__;

        static $dependencies = array();
        static $singletons   = array();

        public static function define($component, $class, $asSingleton = false){
            if ( self::$singletons[ $component ] ){
                unset( self::$singletons[ $component ] );
            }

            self::$dependencies[ $component ] = array($class, $asSingleton);
        }


        public static function get($class){
            return c( $class );
        }
    }
}


namespace {
    
    use framework\di\DI;
    use framework\di\exceptions\DIBindClassNotFound;

    /**
     * @param $class
     * @param bool $onlySingletonInit
     * @return null
     * @throws framework\di\exceptions\DIBindClassNotFound
     * @deprecated
     */
    function c($class, $onlySingletonInit = false){
    
        if ( ($singleton = DI::$singletons[ $class ]) !== null ){
            return $singleton;
        }
        if ( $onlySingletonInit )
            return null;
            
        $binding = DI::$dependencies[ $class ];
        if ( !$binding ){
            /*if ( $ignory )
                return null;
            else*/
                throw new DIBindClassNotFound($class);
        }
            
        $to     = $binding[0];            
        $result = new $to();

        if ( $binding[1] ){
            DI::$singletons[ $class ] = $result;
        }

        return $result;
    }
}