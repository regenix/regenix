<?php
namespace regenix\mvc\http\session;

use regenix\lang\DI;
use regenix\lang\Injectable;
use regenix\lang\Singleton;
use regenix\lang\StrictObject;

/**
 * Class Session
 * @package regenix\mvc
 */
class Session extends StrictObject
    implements Singleton, Injectable {

    const type = __CLASS__;

    private $init = false;
    private $id;
    private $driver;

    protected function __construct(SessionDriver $driver){
        $this->driver = $driver;
    }

    protected function check(){
        if (!$this->init){
            $this->id = $this->driver->getSessionId();
            if (!$this->id)
                session_start();

            $this->init = true;
        }
    }

    public function isInit(){
        return $this->init;
    }

    /**
     * @return string
     */
    public function getId(){
        $this->check();
        return $this->driver->getSessionId();
    }

    /**
     * @return array
     */
    public function all(){
        $this->check();
        return (array)$_SESSION;
    }

    /**
     * @param string $name
     * @param mixed $def
     * @return null|scalar
     */
    public function get($name, $def = null){
        $this->check();
        return $this->has($name) ? $_SESSION[$name] : $def;
    }

    /**
     * @param $name string
     * @param $value string|int|float|null
     */
    public function put($name, $value){
        $this->check();
        $_SESSION[$name] = $value;
    }

    /**
     * @param array $values
     */
    public function putAll(array $values){
        $this->check();
        foreach($values as $name => $value){
            $this->put($name, $value);
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has($name){
        $this->check();
        return isset($_SESSION[$name]);
    }

    /**
     * @param string $name
     */
    public function remove($name){
        $this->check();
        unset($_SESSION[$name]);
    }

    /**
     * clear all session values
     */
    public function clear(){
        $this->check();
        session_unset();
    }

    public static function getInstance() {
        return DI::getInstance(__CLASS__);
    }
}
