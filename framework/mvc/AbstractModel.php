<?php

namespace framework\mvc;


use framework\lang\IClassInitialization;

abstract class AbstractModel implements IClassInitialization {

    const type = __CLASS__;

    /** @var array */
    public $__data = array();

    /**
     * @return mixed
     */
    public function getId(){
        $service = static::getService();
        if ( $service ){
            return $service->getId($this);
        } else
            return null;
    }

    /**
     * @param mixed $value
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


    public function __isset($name){
        return isset($this->__data[$name]);
    }

    public function __unset($name){
        $this->__data[$name] = null;
    }

    /**
     * @return AbstractService
     */
    public static function getService(){
        return AbstractService::get(get_called_class());
    }

    public static function initialize(){
        AbstractService::registerModel(get_called_class());
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

// ANNOTATIONS ...
{
    // @collection annotation
    Annotations::registerAnnotation('collection', array(
        'fields' => array('_arg' => 'string'),
        'require' => array('_arg')
    ), 'class');

    // @indexed .class
    Annotations::registerAnnotation('indexed', array(
        'fields' => array(),
        'multi' => true,
        'any' => true
    ), 'class');

    Annotations::registerAnnotation('ref', array(
        'fields' => array('$lazy' => 'boolean')
    ), 'property');

    Annotations::registerAnnotation('id', array(
        'fields' => array('_arg' => 'string')
    ), 'property');

    // @indexed .property
    Annotations::registerAnnotation('indexed', array(
        'fields' => array()
    ), 'property');

    // @column .property
    Annotations::registerAnnotation('column', array(
        'fields' => array('_arg' => 'string'),
        'require' => array('_arg')
    ), 'property');

    // @length .property
    Annotations::registerAnnotation('length', array(
        'fields' => array('_arg' => 'integer'),
        'require' => array('_arg')
    ), 'property');
}
