<?php
namespace modules\mongodb;

use framework\exceptions\CoreException;
use framework\lang\IClassInitialization;
use framework\lang\String;
use framework\modules\AbstractModule;

abstract class Document implements IClassInitialization {

    const type = __CLASS__;

    /** @var array */
    public $__data = array();

    public function __construct(){

        $service = static::getService();
        $meta    = $service->getMeta();

        foreach($meta['fields'] as $name => $info){
            $this->__data[ $name ] = $this->{$name};
            unset($this->{$name});
        }
    }

    /**
     * @return \MongoId|long|String
     */
    public function getId(){

        $service = static::getService();
        if ( $service ){
            return $service->getId($this);
        } else
            return null;
    }

    /**
     * @param $value
     */
    public function setId($value){

        $service = static::getService();
        if ( $service ){
            $service->setId($this, $value);
        }
    }

    /**
     * @return bool
     */
    public function isNew(){
        return $this->getId() === null;
    }

    public function __set($name, $value){
        $service = static::getService();
        $service->__callSetter($this, $name, $value, true);
    }

    public function __get($name){
        $service = static::getService();
        return $service->__callGetter($this, $name);
    }

    public function __call($name, $args){

        if ( String::startsWith($name, 'get') ){
            $service = static::getService();

            if ( $service ){
                $sym = strtolower($name[3]);
                $name = $sym . substr($name, 4);
                return $service->__callGetter($this, $name);
            } else
                return null;
        } else
            throw CoreException::formated('%s->%s() method not found', get_class($this), $name);
    }

    public function __unset($name){
        $this->__data[$name] = null;
    }

    public function __isset($name){
        return property_exists($this, $name);
    }

    /**
     * @param $field string
     */
    public function unsetField($field){
        $this->__set($field, new AtomicUnset($field));
    }

    /**
     * @return Service
     */
    public static function getService(){
        return Service::get(get_called_class());
    }

    // handle, call on first load class
    public static function initialize(){

        /** @var $module Module */
        $module = Module::getCurrent();
        $module->initConnection();
        $module->loadModel(get_called_class());
    }
}

/** HANDLES */
interface IHandleAfterSave {
    public function onAfterSave($isNew);
}

interface IHandleBeforeSave {
    public function onBeforeSave($isNew);
}

interface IHandleAfterLoad {
    public function onAfterLoad();
}

interface IHandleBeforeLoad {
    public function onBeforeLoad(&$data);
}