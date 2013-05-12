<?php
namespace modules\activerecord;

use framework\mvc\AbstractQuery;
use framework\mvc\AbstractActiveRecord;

class ActiveRecord extends AbstractActiveRecord {

    /** @var \ORM */
    public $__orm;

    /**
     * @param $id
     * @return ActiveRecord
     */
    public static function findById($id){
        return static::getService()->findById($id);
    }

    /**
     * @param AbstractQuery $query
     * @param array $fields
     * @return ModelCursor
     */
    public static function find(AbstractQuery $query = null, array $fields = array()){
        return static::getService()->findByFilter($query, $fields);
    }

    /**
     * @return Service
     */
    public static function getService(){
        return Service::get(get_called_class());
    }


    // handle, call on first load class
    public static function initialize(){
        parent::initialize();

        /** @var $module Module */
        $module = Module::getCurrent();
        $module->initConnection();
    }

    /**
     * @return Query
     */
    public static function query(){
        return new Query(static::getService());
    }
}

class Expression {

    public $value;

    public function __construct($value){
        $this->value = $value;
    }
}