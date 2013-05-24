<?php

namespace framework\mvc\providers;

use framework\exceptions\CoreException;
use framework\lang\ClassScanner;
use framework\mvc\Response;
use framework\lang\String;

abstract class ResponseProvider {

    const type = __CLASS__;

    /**
     * @var Response 
     */
    public $response = null;


    protected function __construct(Response $response) {
        $this->response = $response;
    }
    
    public function getContent(){ return null; }
    public function render(){ }


    private static $providers = array();

    /**
     * @param string $className
     * @return string provider class name
     * @throws CoreException
     */
    public static function get($className){
        if ( $className[0] !== '\\' )
            $className = '\\' . $className;
        
        $provider = self::$providers[ $className ];
        if ( !$provider )
            throw new CoreException(
                    String::format('Response provider not found for "%s" class', $className));
        
        return $provider;
    }
    
    public static function getInstance(Response $response){
        
        $entity = $response->getEntity();
        $providerClass = self::get(get_class($entity));
        
        $result = new $providerClass( $response );
        $result->response = $response;
        
        return $result;
    }

    public static function register($providerClass, $type = false){
        ClassScanner::loadClass($providerClass);
        if ( !$type ){
            $reflect = new \ReflectionClass($providerClass);
            $type = $reflect->getConstant('CLASS_TYPE');
            if ( $type[0] !== '\\' )
                $type = '\\' . $type;
        }

        self::$providers[ $type ] = $providerClass;
    }
}