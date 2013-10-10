<?php
namespace regenix\mvc\http;

use regenix\lang\DI;
use regenix\lang\Singleton;
use regenix\lang\StrictObject;
use regenix\mvc\binding\BindValue;
use regenix\mvc\binding\Binder;
use regenix\mvc\route\RouteInjectable;

class RequestQuery extends StrictObject
    implements Singleton, RouteInjectable {

    const type = __CLASS__;

    /** @var Request */
    private $request;

    /** @var array */
    private $args = null;

    /** @var Binder */
    private $binder;

    public function __construct($query = null, Request $request, Binder $binder) {
        $this->request = $request;
        $this->binder  = $binder;
        $this->args = URL::parseQuery( $query !== null ? $query : $this->request->getQuery() );
    }

    /**
     * get all query arguments
     * @return array
     */
    public function getAll(){
        return $this->args;
    }

    /**
     * checks exists name arg
     * @param $name
     * @return bool
     */
    public function has($name){
        return isset($this->args[$name]);
    }

    /**
     * get one query argument
     * @param string $name
     * @param mixed $def
     * @return mixed
     */
    public function get($name, $def = null){
        $arg  = $this->args[$name];
        return $arg === null ? $def : $arg;
    }

    /**
     * get typed bind value
     * @param $name
     * @param $type
     * @param null $def
     * @return bool|float|BindValue|int|string
     */
    public function getTyped($name, $type, $def = null){
        return $this->binder->getValue($this->get($name, $def), $type);
    }

    /**
     * get integer typed query argument
     * @param string $name
     * @param integer $def
     * @return integer
     */
    public function getNumber($name, $def = 0){
        return (int)$this->get($name, (int)$def);
    }

    /**
     * get string typed query argument
     * @param string $name
     * @param string $def
     * @return string
     */
    public function getString($name, $def = ''){
        return (string)$this->get( $name, (string)$def );
    }


    /**
     *
     * @param string $name
     * @param boolean $def
     * @return boolean
     */
    public function getBoolean($name, $def = false){
        return (boolean)$this->get($name, (boolean)$def);
    }

    /**
     * get array query argument
     * @param string $name
     * @param array $def
     * @return array
     */
    public function getArray($name, array $def = array()){
        $arg = $this->get($name, (array)$def);
        if (is_array( $arg ))
            return $arg;
        else
            return array($arg);
    }

    /**
     * get array typed from query string
     * @param string $name
     * @param string $type string|boolean|integer|double|array
     * @param array $def
     * @return array
     */
    public function getArrayTyped($name, $type = 'string', array $def = array()){
        $arg = $this->getArray($name, $def);
        foreach($arg as &$v){
            $v = $this->binder->getValue($v, $type);
        }
        return $arg;
    }

    /**
     * get array from explode of query argument
     * @param string $name
     * @param string $delimiter
     * @param array $def
     * @return array
     */
    public function getExplode($name, $delimiter = ',', array $def = array()){
        $arg = $this->get($name, null);
        if ( $arg === null || is_array( $arg) )
            return (array)$def;

        return explode($delimiter, (string)$arg, 300);
    }

    /**
     * get array typed from explode of query argument
     * @param string $name
     * @param string $type
     * @param string $delimiter
     * @param array $def
     * @return array
     */
    public function getExplodeTyped($name, $type = 'string', $delimiter = ',', array $def = array()){
        $arg = $this->getExplode($name, $delimiter, $def);
        foreach($arg as &$v){
            $v = $this->binder->getValue($v, $type);
        }
        return $arg;
    }
}