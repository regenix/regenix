<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dim-s
 * Date: 14.03.13
 * Time: 12:26
 * To change this template use File | Settings | File Templates.
 */

namespace framework\lang;


class ArrayTyped {

    protected $data;

    public function __construct(array $data = null){
        $this->data = $data == null ? array() : $data;
    }

    public function getDefault($def = null){
        return $this->get('_arg', $def);
    }

    /**
     * @param string $name
     * @return boolean
     */
    public function has($name){
        return isset($this->data[$name]);
    }

    /**
     * @param string $name
     * @param mixed $def
     * @return mixed
     */
    public function get($name, $def = null){
        $value = $this->data[$name];
        return isset($value) ? $value : $def;
    }

    public function getKeys(){
        return array_keys($this->data);
    }

    /**
     * @param string $name
     * @param string $def
     * @return string
     */
    public function getString($name, $def = ''){
        $value = $this->data[$name];
        return isset($value) ? (string)$value : (string)$def;
    }

    /**
     * @param string $name
     * @param bool $def
     * @return bool
     */
    public function getBoolean($name, $def = false){
        $value = $this->data[$name];
        return isset($value) ?
            $value !== '' && $value !== '0' && $value !== 'false'
            : (boolean)$def;
    }

    /**
     * @param string $name
     * @param int $def
     * @return int
     */
    public function getInteger($name, $def = 0){
        $value = $this->data[$name];
        return isset($value) ? (int)$value : (int)$def;
    }

    /**
     * @param string $name
     * @param float $def
     * @return float
     */
    public function getDouble($name, $def = 0.0){
        $value = $this->data[$name];
        return isset($value) ? (double)$value : (double)$def;
    }
}