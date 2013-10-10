<?php
namespace regenix\deps;

use regenix\lang\CoreException;
use regenix\lang\String;

abstract class Origin {

    const type = __CLASS__;
    const PREFIX = '???';

    protected $env;
    private $address;

    public function __construct($address){
        $this->address = $address;
    }

    /**
     * @param string $origin
     * @return bool
     */
    public static function isCurrent($origin){
        $is = String::startsWith($origin, static::PREFIX);
        return $is;
    }

    /**
     * @return int time unix
     */
    abstract public function lastUpdated();

    /**
     * @param $group
     * @return array
     */
    abstract public function getMetaInfo($group);

    /**
     * @return mixed
     */
    public function getAddress(){
        return $this->address;
    }

    /**
     * @param string $group
     * @param string $version
     * @param string $name
     * @param string $toDir
     * @return mixed
     */
    abstract public function downloadDependency($group, $version, $name, $toDir);

    /**
     * @param string $env
     */
    public function setEnv($env){
        $this->env = $env;
    }


    private static $originTypes = array();

    /**
     * @param $originClass
     * @throws \regenix\lang\CoreException
     */
    public static function register($originClass){
        if (REGENIX_IS_DEV){
            $reflect = new \ReflectionClass($originClass);
            if (!$reflect->isSubclassOf(Origin::type)){
                throw new CoreException('Repository origin should be inherited by the `%s` class', Origin::type);
            }
        }

        self::$originTypes[] = $originClass;
    }

    /**
     * @param string $address
     * @return Origin|null
     */
    public static function createOriginByAddress($address){
        foreach(self::$originTypes as $type){
            if ($type::isCurrent($address)){
                return new $type($address);
            }
        }

        return null;
    }
}
