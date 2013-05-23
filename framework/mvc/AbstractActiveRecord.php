<?php
namespace framework\mvc;

use framework\exceptions\CoreException;
use framework\exceptions\StrictObject;
use framework\exceptions\TypeException;
use framework\lang\IClassInitialization;

abstract class AbstractActiveRecord extends StrictObject
    implements IClassInitialization {

    const type = __CLASS__;

    /**
     * @ignore
     * @var bool
     */
    public $__fetched = false;

    /**
     * @ignore
     * @var array
     */
    public $__data = array();

    /**
     * @ignore
     * @var array
     */
    public $__modified = array();

    /** @var array */
    private static $__handle = array();

    public function __construct(){
        $service = static::getService();
        $meta    = $service->getMeta();

        foreach($meta['fields'] as $name => $info){
            $this->__data[ $name ] = $this->{$name};
            unset($this->{$name});
        }

        $traits = class_uses_all($this);
        foreach($traits as $trait){
            if (method_exists($trait, 'construct'))
                $trait::construct($this);
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
     * @param $value
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
     * @param $other
     * @return bool
     */
    public function equals($other){
        return $other != null && $other instanceof AbstractActiveRecord && $this->getId() === $other->getId();
    }

    /**
     * @return bool
     */
    public function isModified(){
        return sizeof($this->__modified) > 0;
    }

    public function __set($name, $value){
        $service = static::getService();
        $meta    = $service->getMeta();
        if ($meta['fields'][$name]['readonly'])
            throw CoreException::formated('Property `%s` for read only, at `%s` class', $name, get_class($this));

        $service->__callSetter($this, $name, $value, true);
    }

    public function __get($name){
        $service = static::getService();
        return $service->__callGetter($this, $name);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getRawValue($name){
        if (!array_key_exists($name, $this->__data))
            return parent::__get($name);

        return $this->__data[$name];
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

    /**
     * @param string $where
     * @internal param $values ...
     * @return ActiveRecordCursor
     */
    public static function filter($where = ''){
        $query = static::query();
        if ($where)
            call_user_func_array(array($query, 'filter'), func_get_args());

        return static::find($query);
    }

    /**
     * @param string $key
     * @param string|AbstractQuery $whereOrQuery
     * @return array
     */
    public static function distinct($key, $whereOrQuery = ''){
        if (!($whereOrQuery instanceof AbstractQuery)){
            $query = static::query();
            if ($whereOrQuery)
                call_user_func_array(array($query, 'filter'), array_slice(func_get_args(),1));
        }

        return static::getService()->distinct($whereOrQuery, $key);
    }

    /**
     * @param string $where
     * @return AbstractQuery
     */
    abstract public static function query($where = '');

    public function __clone(){
        $this->setId(null);
    }

    public static function initialize(){
        $service = static::getService();

        if ($service){
            $class = get_called_class();
            $service->registerModel($class);

            $traits = class_uses_all($class);
            foreach($traits as $trait){
                $simpleName = array_pop(explode('\\', $trait));
                if (method_exists($trait, $simpleName . '_initialize')){
                    call_user_func(array($trait, $simpleName . '_initialize'), $class);
                }
            }
        }
    }

    public static function addHandle($event, $callback){
        if (IS_DEV && !is_callable($callback))
            throw new TypeException('$callback', 'callable');

        $class = get_called_class();
        self::$__handle[$class][$event][] = $callback;
    }

    public static function callHandle($event){
        $class = get_called_class();
        $args  = array_slice(func_get_args(), 1);

        foreach((array)self::$__handle[$class][$event] as $callback)
            call_user_func_array($callback, $args);
    }
}

/** HANDLES */
interface IHandleAfterSave {
    public function onAfterSave($isNew);
}

interface IHandleBeforeSave {
    public function onBeforeSave($isNew);
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
            'fields' => array('_arg' => 'mixed')
        ), 'property');

        // @timestamp
        Annotations::registerAnnotation('timestamp', array(
            'fields' => array('_arg' => 'mixed')
        ), 'property');

        // @ignore
        Annotations::registerAnnotation('ignore', array(
            'fields' => array('_arg' => 'mixed')
        ), 'property');

        // @readonly
        Annotations::registerAnnotation('readonly', array(
            'fields' => array('_arg' => 'mixed')
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