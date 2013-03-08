<?php

namespace framework\di {

    use framework\di\exceptions\DIException;
    use framework\utils\ClassLoader;
    use framework\utils\StringUtils;
    
class DI {

    static $dependencies = array();
    static $singletons   = array();

    public static function bind($class, $alias = null){

        if ( self::$singletons[ $class ] ){
            unset( self::$singletons[ $class ] );
        }
        
        if ($alias === null){
            if (($p = strrpos( $class, '\\' )) !== FALSE)
                $alias = substr ($class, $p + 1);
            else
                $alias = $class;
        }
        
        $binding = new _DIBinding($class);
        self::$dependencies[ $alias ] = $binding;

        return $binding;
    }


    public static function get($class){

        return c( $class );
    }
}


    class _DIBinding {

        private $class;
        public $to;
        public $singleton = false;

        public function __construct($class){

            ClassLoader::load($class);

            $this->class = $class;
            $this->to( $class );
        }

        public function to($class){

            ClassLoader::load($class);

            if ( $class != $this->class ){
                $reflection = new \ReflectionClass($class);
                if ( !$reflection->isSubclassOf($this->class) ){
                    throw new DIException( 
                                StringUtils::format('Class "%s" must be subclass of "%s"', $class, $this->class) 
                            );
                }
            }

            $this->to = $class;
            return $this;
        }

        public function getClass(){
            return $this->class;
        }

        public function getTo(){
            return $this->to;
        }

        public function asSingleton(){
            $this->singleton = true;
            return $this;
        }

        public function isSingleton(){
            return $this->singleton;
        }
    }

}


namespace {
    
    use framework\di\DI;
    use framework\di\exceptions\DIBindClassNotFound;
    
    function c($class){
    
        if ( ($singleton = DI::$singletons[ $class ]) !== null ){
            return $singleton;
        }

        $binding = DI::$dependencies[ $class ];
        if ( !$binding )
            throw new DIBindClassNotFound($class);

        $to     = $binding->to;
        $result = new $to();

        if ( $binding->singleton ){
            DI::$singletons[ $class ] = $result;
        }

        return $result;
    }
}