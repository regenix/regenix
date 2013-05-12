<?php
namespace modules\mongodb;

use framework\exceptions\CoreException;
use framework\modules\AbstractModule;
use framework\mvc\AbstractQuery;
use framework\mvc\AbstractService;
use framework\mvc\AbstractActiveRecord;

abstract class ActiveRecord extends AbstractActiveRecord {

    const type = __CLASS__;

    /**
     * @param $id
     * @param mixed $id
     * @return ActiveRecord|null
     */
    public static function findById($id){
        return static::getService()->findById($id);
    }

    /**
     * @param Query $query
     * @param array $fields
     * @return DocumentCursor
     */
    public static function find(AbstractQuery $query = null, array $fields = array()){
        return static::getService()->findByFilter($query, $fields);
    }

    public static function findAndModify(Query $query, array $update, array $fields = array()){
        return static::getService()->findByFilterAndModify($query, $update, $fields);
    }

    /**
     * @return Service
     */
    public static function getService(){
        $class = get_called_class();
        return Service::get($class);
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