<?php
namespace regenix\lang\types;

class Callback {

    const type = __CLASS__;

    protected $callback;

    /**
     * @param string|object|\Closure $name
     * @param string|null $method
     * @throws \InvalidArgumentException
     */
    public function __construct($name, $method = null){
        if ($name instanceof \Closure || $name === null){
            $this->callback = $name;
            return;
        } elseif (is_object($name)) {
            $this->callback = array($name, $method);
        } elseif ($method) {
            $this->callback = $name . '::' . $method;
        } else {
            $this->callback = $name;
        }

        if (!is_callable($this->callback)){
            throw new \InvalidArgumentException('Invalid arguments for Callback: ' . $this->toString());
        }
    }

    /**
     * @return mixed
     */
    public function __invoke(){
        if ($this->callback !== null)
            return call_user_func_array($this->callback, func_get_args());
    }

    /**
     * @param args...
     * @return mixed
     */
    public function invoke(){
        if ($this->callback !== null)
            return call_user_func_array($this->callback, func_get_args());
    }

    /**
     * @param array $args
     * @return mixed
     */
    public function invokeArgs(array $args){
        if ($this->callback !== null)
            return call_user_func_array($this->callback, $args);
    }

    public function toString(){
        if ($this->callback instanceof \Closure){
            return (string)$this->callback;
        } elseif (is_array($this->callback)){
            $class = get_class($this->callback[0]);
            return $class . '->' . $this->callback[1];
        } else {
            return $this->callback;
        }
    }

    public function __toString(){
        return $this->toString();
    }

    public function isNop(){
        return $this->callback === null;
    }

    public function isClosure(){
        return $this->callback instanceof \Closure;
    }

    public function isStaticMethod(){
        return is_string($this->callback) && strpos($this->callback, '::') !== false;
    }

    public function isDynamicMethod(){
        return is_array($this->callback);
    }

    public function isFunction(){
        return is_string($this->callback) && !$this->isStaticMethod();
    }

    private static $nop = null;

    public static function nop(){
        if (self::$nop)
            return self::$nop;

        return self::$nop = new Callback(null);
    }
}