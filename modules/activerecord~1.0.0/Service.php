<?php
namespace modules\activerecord;

use framework\exceptions\CoreException;
use framework\mvc\AbstractQuery;
use framework\mvc\AbstractService;
use framework\mvc\AbstractActiveRecord;
use framework\mvc\ActiveRecordCursor;
use framework\mvc\IHandleAfterSave;
use framework\mvc\IHandleBeforeSave;
use framework\mvc\QueryException;

class Service extends AbstractService {

    /**
     * @return bool
     */
    public function beginTransaction(){
        return \ORM::get_db()->beginTransaction();
    }

    /**
     * @return bool
     */
    public function inTransaction(){
        return \ORM::get_db()->inTransaction();
    }

    public function commit(){
        return \ORM::get_db()->commit();
    }

    public function rollback(){
        return \ORM::get_db()->rollBack();
    }

    /**
     * @param \framework\mvc\AbstractActiveRecord $object
     * @param \ORM $orm
     * @param AbstractActiveRecord $object
     * @return bool
     */
    protected function putData(AbstractActiveRecord $object, \ORM $orm){
        $meta  = $this->getMeta();
        $isNew = $object->isNew();

        $result = false;
        foreach($meta['fields'] as $field => $info){
            if (!$isNew && !$object->__modified[$field]) continue;
            if ( $field === $meta['id_field']['field'] ) continue;

            $value = $this->__callGetter($object, $field);

            if ($value instanceof Expression){
                $orm->set_expr($info['column'], $value->value);
                $result = true;
            } else {
                $value = self::typed($value, $info['type'], $info['ref']);
                $orm->set($info['column'], $value);
            }
        }
        return $result;
    }

    /**
     * @param AbstractActiveRecord $object
     * @return \ORM
     */
    protected function getORM(AbstractActiveRecord $object){
        $meta = $this->getMeta();
        /** @var $orm \ORM */
        $orm  = $object->__orm;
        if (!$orm){
            $orm = \ORM::for_table($meta['collection'])->create();
            $object->__orm = $orm;
        }
        $this->putData($object, $orm);
        $orm->use_id_column($meta['id_field']['column']);
        return $orm;
    }

    /**
     * @param \framework\mvc\AbstractActiveRecord|\modules\activerecord\ActiveRecord $object
     * @param array $options
     */
    public function save(AbstractActiveRecord $object, array $options = array()){
        $orm = $this->getORM($object);

        $isNew = $object->isNew();
        if ($object instanceof IHandleBeforeSave){
            $object->onBeforeSave($isNew);
            $this->putData($object, $orm);
        }

        $result = $orm->save();
        if (!$result){
            $error = \ORM::get_last_statement()->errorInfo();
            throw new \PDOException("[SQL Error {$error[0]}:{$error[1]}] " . $error[2]);
        }

        if ($isNew)
            $this->setId($object, $orm->id());

        if ($object instanceof IHandleAfterSave){
            $object->onAfterSave($isNew);
        }

        $object->__modified = array();
    }

    public function remove(AbstractActiveRecord $object, array $options = array()){
        /** @var $orm \ORM */
        $orm = $this->getORM($object);
        if (!$object->isNew()){
            $meta = $this->getMeta();
            $orm->use_id_column($meta['id_field']['column']);
            $orm->delete();
            $this->setId($object, null);
        }
    }

    /**
     * @param $value
     * @param array $fields
     * @param bool $lazy
     * @return mixed
     */
    protected function findDataById($value, array $fields = array(), $lazy = false){
        $meta = $this->getMeta();
        $q = \ORM::for_table($meta['collection']);
        $q->use_id_column($meta['id_field']['column']);

        $orm  = $q->find_one($value);
        if ($orm){
            return $orm->as_array();
        } else
            return null;
    }

    /**
     * @param AbstractQuery $query
     * @param array $fields
     * @param bool $lazy
     * @return ModelCursor
     */
    public function findByFilter(AbstractQuery $query, array $fields = array(), $lazy = false){
        if (!$query)
            $query = new Query($this);

        return new ModelCursor($query->getOrm_(), $fields, $this, $lazy);
    }

    public static function typed($value, $type, $ref = null){
        return $value;
    }

    protected static function newInstance($modelClass){
        return new Service($modelClass);
    }
}

class ModelCursor extends ActiveRecordCursor {

    /** @var \ORM */
    private $orm;

    /** @var AbstractService */
    private $service;

    /** @var bool */
    private $lazy;

    /** @var \IdiormResultSet */
    private $result_ = null;

    /** @var \ArrayIterator */
    private $resultIterator_ = null;

    public function __construct(\ORM $orm, array $fields, AbstractService $service, $lazy = false){
        foreach($fields as $value){
            if ($value instanceof Expression)
                $orm->select_expr($value);
            else
                $orm->select($value);
        }

        $this->orm = $orm;

        $this->service = $service;
        $this->lazy    = $lazy;
    }

    /**
     * @return \IdiormResultSet
     */
    private function getResult(){
        if ($this->result_ === null){
            $this->result_ = $this->orm->find_result_set();
            $this->resultIterator_ = $this->result_->getIterator();
        }

        return $this->result_;
    }

    /**
     * @return \ArrayIterator
     */
    private function getIterator(){
        $this->getResult();
        return $this->resultIterator_;
    }

    public function sort(array $fields){
        foreach($fields as $name => $type){
            $column = $this->getColumn($name);

            if ($type instanceof Expression ){
                $this->orm->order_by_expr($type->value);
                continue;
            }

            $type = strtolower($type);
            switch($type){
                case '0':
                case '1':
                case 'asc': {
                    $this->orm->order_by_asc($column);
                } break;

                case '-1':
                case 'desc': {
                    $this->orm->order_by_desc($column);
                } break;

                default: {
                    CoreException::showOnlyPublic(true);
                    throw CoreException::formated('Unknown sort type `%s` for `%s` field', $type, $name);
                }
            }
        }

        return $this;
    }

    public function skip($value){
        $this->orm->offset($value);
        return $this;
    }

    public function limit($value){
        $this->orm->limit($value);
        return $this;
    }

    public function count(){
        return $this->orm->count();
    }

    private function getColumn($field){
        $meta = $this->service->getMeta();
        $column = $meta['fields'][$field]['column'];
        if (!$column){
            CoreException::showOnlyPublic(true);
            throw QueryException::formated('field `%s` not exists in `%s` document type', $field,
                $this->service->getModelClass());
        }
        return $column;
    }

    public function max($field){
        return $this->orm->max($this->getColumn($field));
    }

    public function min($field){
        return $this->orm->min($this->getColumn($field));
    }

    public function avg($field){
        return $this->orm->avg($this->getColumn($field));
    }

    public function sum($field){
        return $this->orm->sum($this->getColumn($field));
    }

    public function explain(){
        return $this->orm->explain();
    }

    public function current(){
        $data = $this->getIterator()->current();
        if (!$data)
            return null;

        $modelClass = $this->service->getModelClass();
        return $this->service->fetch(new $modelClass, $data->as_array(), $this->lazy);
    }

    public function next(){
        $this->getIterator()->next();
    }

    public function key(){
        return $this->getIterator()->key();
    }

    public function valid(){
        return $this->getIterator()->valid();
    }

    public function rewind(){
        $this->getIterator()->rewind();
    }
}


class Query extends AbstractQuery {

    /**
     * @param string|Expression $name
     * @return $this
     */
    public function groupBy($name){
        if (is_string($name))
            $this->field($name);

        $this->data['$group_by'][] = $name;
        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function distinct($field){
        return $this->popValue($field, true, '$distinct');
    }

    /**
     * @param $expr
     * @param array $args
     * @return $this
     */
    public function raw($expr, array $args = array()){
        $this->data['$raw'][] = array($expr, $args);
        return $this;
    }

    /**
     * @return \ORM
     * @throws
     */
    public function getOrm_(){
        $data = $this->getData();
        $meta = $this->meta;

        $orm = \ORM::for_table($meta['collection']);
        foreach($data as $key => $value){
            switch($key){
                case '$eq': {
                    foreach($value as $column => $val){
                        if ($value === null)
                            $orm->where_null($column);
                        else
                            $orm->where_equal($column, $val);
                    }
                } break;
                case '$neq': {
                    foreach($value as $column => $val){
                        if ($value === null)
                            $orm->where_not_null($column);
                        else
                            $orm->where_not_equal($column, $val);
                    }
                } break;
                case '$null': {
                    foreach($value as $column => $val)
                        $orm->where_null($column);
                } break;
                case '$nnull': {
                    foreach($value as $column => $val)
                        $orm->where_not_null($column);
                } break;
                case '$in': {
                    foreach($value as $column => $val) $orm->where_in($column, $value);
                } break;
                case '$nin': {
                    foreach($value as $column => $val) $orm->where_not_in($column, $val);
                } break;
                case '$gt': {
                    foreach($value as $column => $val) $orm->where_gt($column, $val);
                } break;
                case '$gte': {
                    foreach($value as $column => $val) $orm->where_gte($column, $val);
                } break;
                case '$lt': {
                    foreach($value as $column => $val) $orm->where_lt($column, $val);
                } break;
                case '$lte': {
                    foreach($value as $column => $val) $orm->where_lte($column, $val);
                } break;
                case '$like': {
                    foreach($value as $column => $val) $orm->where_like($column, $val);
                } break;
                case '$nlike': {
                    foreach($value as $column => $val) $orm->where_not_like($column, $val);
                } break;
                case '$raw': {
                    foreach($value as $val) $orm->where_raw($val[0], $val[1]);
                } break;
                case '$group_by': {
                    foreach($value as $val){
                        if ($val instanceof Expression)
                            $orm->group_by_expr($val->value);
                        else
                            $orm->group_by($val);
                    }
                } break;
                case '$distinct': {
                    foreach($value as $column => $val){
                        $orm->distinct()->select($column);
                    }
                } break;
                case '$or': {
                    // TODO realize
                    throw QueryException::formated('Statement `OR` is not supported');
                } break;
            }
        }

        return $orm;
    }
}