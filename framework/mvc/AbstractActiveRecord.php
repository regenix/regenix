<?php
namespace framework\mvc;

use framework\exceptions\CoreException;
use framework\exceptions\StrictObject;
use framework\lang\IClassInitialization;

abstract class AbstractActiveRecord extends StrictObject
    implements IClassInitialization {

    const type = __CLASS__;

    public $__fetched = false;

    /** @var array */
    public $__data = array();

    /** @var array */
    public $__modified = array();

    public function __construct(){
        $service = static::getService();
        $meta    = $service->getMeta();

        foreach($meta['fields'] as $name => $info){
            $this->__data[ $name ] = $this->{$name};
            unset($this->{$name});
        }
    }


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
        return !$this->__fetched || $this->getId() === null;
    }

    /**
     * @return bool
     */
    public function isModified(){
        return sizeof($this->__modified) > 0;
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
        $this->__modified[$name] = true;
    }

    public function save(array $options = array()){
        static::getService()->save($this, $options);
        return $this;
    }

    public function reload(array $fields = array()){
        static::getService()->reload($this, $fields);
        return $this;
    }

    public function delete(array $options = array()){
        static::getService()->remove($this, $options);
        return $this;
    }

    /**
     * @return AbstractService
     */
    public static function getService(){
        return AbstractService::get(get_called_class());
    }

    public static function beginTransaction(){
        return static::getService()->beginTransaction();
    }

    public static function inTransaction(){
        return static::getService()->inTransaction();
    }

    public static function commit(){
        return static::getService()->commit();
    }

    public static function rollback(){
        return static::getService()->rollback();
    }

    /**
     * @param $id
     * @return mixed
     */
    abstract public static function findById($id);

    /**
     * @param AbstractQuery $query
     * @param array $fields
     * @return mixed
     */
    abstract static function find(AbstractQuery $query = null, array $fields = array());

    /** @return AbstractQuery */
    abstract public static function query();

    public function __clone(){
        $this->setId(null);
    }

    public static function initialize(){
        $service = static::getService();

        if ($service){
            $service->registerModel(get_called_class());
        }
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

interface IHandleAfterRemove {
    public function onAfterRemove();
}

interface IHandleBeforeRemove {
    public function onBeforeRemove();
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

        // @timestamp
        Annotations::registerAnnotation('timestamp', array(
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