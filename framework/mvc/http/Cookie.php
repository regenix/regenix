<?php
namespace regenix\mvc\http;

use regenix\lang\ArrayTyped;
use regenix\lang\DI;
use regenix\lang\Injectable;
use regenix\lang\Singleton;
use regenix\lang\StrictObject;
use regenix\libs\Time;

class Cookie extends StrictObject
    implements Singleton, Injectable {

    const type = __CLASS__;

    /**
     * @var ArrayTyped
     */
    private $data;

    protected function __construct(){
        $this->data = new ArrayTyped($_COOKIE);
    }

    /**
     * @param string $name
     * @param string|int|float|boolean $value
     * @param null|int|string $expires
     */
    public function put($name, $value, $expires = null){
        setcookie($name, $value, $expires ? Time::parseDuration($expires) : $expires, '/');
        $this->data = new ArrayTyped($_COOKIE);
    }

    /**
     * @return ArrayTyped
     */
    public function all(){
        return $this->data;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has($name){
        return $this->data->has($name);
    }

    /**
     * @param string $name
     * @param null $def
     * @return mixed
     */
    public function get($name, $def = null){
        return $this->data->get($name, $def);
    }

    /**
     * @param string $name
     * @param string $def
     * @return string
     */
    public function getString($name, $def = ''){
        return $this->data->getString($name, $def);
    }

    /**
     * @param string $name
     * @param bool $def
     * @return bool
     */
    public function getBoolean($name, $def = false){
        return $this->data->getBoolean($name, $def);
    }

    /**
     * @param string $name
     * @param int $def
     * @return int
     */
    public function getInteger($name, $def = 0){
        return $this->data->getInteger($name, $def);
    }

    /**
     * @param string $name
     * @param float $def
     * @return float
     */
    public function getDouble($name, $def = 0.0){
        return $this->data->getDouble($name, $def);
    }

    /**
     * @return Cookie
     */
    public static function getInstance() {
        return DI::getInstance(__CLASS__);
    }
}