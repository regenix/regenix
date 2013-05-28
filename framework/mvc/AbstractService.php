<?php
namespace regenix\mvc;

use regenix\lang\StrictObject;
use regenix\exceptions\AnnotationException;
use regenix\lang\CoreException;
use regenix\lang\ArrayTyped;
use regenix\lang\String;

abstract class AbstractService extends StrictObject {

    /** @var array */
    protected static $modelInfo = array();

    /** @var string */
    protected $modelClass;

    /** @var array */
    private $meta_;

    /**
     * @param $modelClass string
     */
    protected function __construct($modelClass){
        $this->modelClass = $modelClass;
    }

    /**
     * @return string
     */
    public function getModelClass(){
        return $this->modelClass;
    }

    /**
     * @return array
     */
    public function getMeta(){
        if (!$this->meta_)
            $this->meta_ = self::$modelInfo[$this->modelClass];

        return $this->meta_;
    }

    /**
     * @param $modelClass
     * @return AbstractService
     */
    protected static function newInstance($modelClass){
        return null; // TODO
    }

    /**
     * @var AbstractService[]
     */
    private static $services = array();

    public function beginTransaction(){
        throw CoreException::formated('Transactions are not supported');
    }

    public function inTransaction(){
        throw CoreException::formated('Transactions are not supported');
    }

    public function commit(){
        throw CoreException::formated('Transactions are not supported');
    }

    public function rollback(){
        throw CoreException::formated('Transactions are not supported');
    }

    /**
     * @param $modelClass
     * @return AbstractService
     */
    public static function get($modelClass){
        if ( $result = self::$services[$modelClass] )
            return $result;

        return self::$services[$modelClass] = static::newInstance($modelClass);
    }

    abstract public function save(AbstractActiveRecord $object, array $options = array());
    abstract public function remove(AbstractActiveRecord $object, array $options = array());

    /**
     * @param AbstractActiveRecord[] $documents
     * @param array $options
     * @return array of bool
     */
    public function saveAll(array $documents, array $options = array()){
        $result = array();
        foreach($documents as $document){
            $result[] = $this->save($document, $options);
        }
        return $result;
    }

    /**
     * @param array $objects
     * @param array $options
     * @return array
     */
    public function removeAll(array $objects, array $options = array()){
        $result = array();
        foreach($objects as $document){
            $result[] = $this->remove($document, $options);
        }
        return $result;
    }

    public function fetch(AbstractActiveRecord $object, $data, $lazyNeed = false){
        $meta = $this->getMeta();
        foreach($data as $column => $value){
            $field = $meta['fields_rev'][ $column ];
            if ( $field ){
                $info = $meta['fields'][$field];
                $value = $this->typedFetch($value, $info);

                $this->__callSetter($object, $field, $value, $lazyNeed);
            }
        }

        $valueId = $data[$meta['id_field']['column']];
        $this->setId($object, $valueId);
        $object->__fetched = true;

        $object->__modified = array();
        return $object;
    }

    /**
     * @param AbstractActiveRecord $object
     * @param bool $typed
     * @return mixed|null
     */
    public function getId(AbstractActiveRecord $object, $typed = true){
        $meta = $this->getMeta();
        $idField = $meta['id_field']['field'];
        if ( $idField ){
            return $typed
                ? $this->typed($object->__data[$idField], $meta['id_field'])
                : $object->__data[$idField];
        } else
            return null;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function isReference($value){
        return is_scalar($value);
    }

    /**
     * @param $value
     * @param array $fields
     * @param bool $lazy
     * @return mixed
     */
    abstract protected function findDataById($value, array $fields = array(), $lazy = false);

    /**
     * @param mixed $id
     * @param array $fields
     * @param bool $lazy
     * @return AbstractActiveRecord
     */
    public function findById($id, array $fields = array(), $lazy = false){
        $meta    = $this->getMeta();
        $idField = $meta['id_field'];
        $data  = $this->findDataById($this->typed($id, $idField));
        if ( $data === null )
            return null;

        $modelClass = $this->modelClass;
        return $this->fetch(new $modelClass(), $data, $lazy);
    }

    /**
     * @param AbstractQuery $query
     * @param array $fields
     * @param bool $lazy
     * @return ActiveRecordCursor
     */
    abstract public function findByFilter(AbstractQuery $query, array $fields = array(), $lazy = false);

    /**
     * @param AbstractQuery $query
     * @param string $key
     * @return array
     */
    abstract public function distinct(AbstractQuery $query, $key);

    /**
     * @param mixed $value
     * @param array $fields
     * @param bool $lazy
     * @return AbstractActiveRecord
     */
    protected function findByRef($value, array $fields = array(), $lazy = false){
        return $this->findById($value, $fields, $lazy);
    }

    /**
     * @param AbstractActiveRecord $object
     * @param array $fields
     * @throws static
     * @return bool
     */
    public function reload(AbstractActiveRecord $object, array $fields = array()){
        $id = $this->getId($object);
        if ( !$id )
            throw CoreException::formated('Can`t reload non-exist document');

        $data = $this->findDataById($id, $fields);
        if ( $data ){
            $this->fetch($object, $data);
            return true;
        } else
            return false;
    }

    public function __callGetter(AbstractActiveRecord $object, $field){
        $meta  = $this->getMeta();
        $info  = $meta['fields'][$field];
        if (!$info)
            throw CoreException::formated('Property `%s` not defined in `%s` class', $field, get_class($this));

        $value = $object->__data[$field];

        if ( $info['ref'] && $info['ref']['lazy'] ){
            if ( $info['is_array'] ){

                $type = $info['array_type'];
                $service = $type::getService();

                if (!is_array($value))
                    $value = $value ? array($value) : array();

                $needSet = false;
                if (is_array($value))
                foreach($value as &$el){
                    if ($service->isReference($el)){
                        $el = $service->findByRef($el);
                        $needSet = true;
                    }
                }
                unset($el);
                if ($needSet)
                    $this->__callSetter($object, $field, $value);

            } else {
                $type = $info['type'];

                /** @var $service AbstractService */
                $service = $type::getService();
                if ( $service->isReference($value) ){
                    $value = $service->findByRef($value);
                    $this->__callSetter($object, $field, $value);
                }
            }
        }
        return $value;
    }

    public function __callSetter(AbstractActiveRecord $object, $field, $value, $lazy = false){
        $meta = $this->getMeta();
        $info = $meta['fields'][$field];

        if ($info['ref'] && !$info['ref']['lazy'] && $lazy === false){

            $type = $info['type'];
            /** @var $service AbstractService */

            if ($info['is_array']){
                $type = $info['array_type'];
                $service = $type::getService();
                if (!is_array($value))
                    $value = $value ? array($value) : array();

                foreach($value as &$el){
                    $el = $service->findByRef($el, array(), true);
                }
                unset($el);

            } else {
                $service = $type::getService();
                $value   = $service->findByRef($value, array(), true);
            }
        }

        if ($info['setter']){
            $method = 'set' . $field;
            $object->{$method}($value);
        }

        $object->__data[$field] = $value;
        $object->__modified[$field] = true;
    }

    /**
     * @param AbstractActiveRecord $object
     * @param $id
     * @throws static
     */
    public function setId(AbstractActiveRecord $object, $id){
        $meta    = $this->getMeta();
        $idField = $meta['id_field'];
        if ( $idField ){
            $field = $idField['field'];
            $object->__data[$field] = $this->typedFetch($id, $idField);
        } else
            throw CoreException::formated('Document `%s` has no @id field', $meta['name']);
    }

    protected static function registerModelMetaClass(&$info, \ReflectionClass $class, Annotations $classInfo){
        $name = $class->getName();
        $anCollection = $classInfo->get('collection');

        $info['collection'] = $anCollection->getDefault( $name );
    }

    protected static function registerModelMetaId(&$propInfo, &$allInfo,
                                                  \ReflectionClass $class, $name, Annotations $property){
        $allInfo['id_field'] = array(
            'column' => $propInfo['column'] ? $propInfo['column'] : 'id',
            'field' => $propInfo['name'], 'type' => $propInfo['type']
        );
    }

    protected static function registerModelMetaProperty(&$cur, \ReflectionClass $class, $name, Annotations $property){
        $cur['name']   = $name;
        $cur['column'] = $property->get('column')->getDefault( $name );
        $cur['type']   = $property->get('var')->getDefault('mixed');
        $cur['timestamp'] = $property->has('timestamp');
        $cur['readonly']  = $property->has('readonly');

        if ( $property->has('ref') ){
            $cur['ref'] = array(1,
                'lazy' => $property->get('ref')->getBoolean('$lazy'),
                'small' => $property->get('ref')->getBoolean('$small')
            );
            if ( !String::startsWith('models\\', $cur['type']) )
                $cur['type'] = 'models\\' . $cur['type'];
        }

        if ( String::endsWith($cur['type'], '[]') ){
            $cur['is_array']   = true;
            $cur['array_type'] = substr($cur['type'], 0, -2);
        }

        $cur['length'] = $property->get('length')->getDefault(0);

        // setter
        if ( $class->hasMethod('set' . $name) )
            $cur['setter'] = true;

        // getter
        if ( $class->hasMethod('get' . $name) )
            $cur['getter'] = true;
    }

    /**
     * @param $infoIndex
     * @param $allInfo
     * @param Annotations $classInfo
     * @param $key
     * @param ArrayTyped $indexed
     * @throws \regenix\exceptions\AnnotationException
     */
    protected static function registerModelMetaIndex(&$infoIndex, &$allInfo, Annotations $classInfo, $key, ArrayTyped $indexed){

    }

    /**
     * @param \ReflectionClass $class
     * @param Annotations $classInfo
     * @param Annotations[] $propertiesInfo
     * @throws \regenix\exceptions\AnnotationException
     */
    protected static function registerModelMeta(\ReflectionClass $class, Annotations $classInfo, $propertiesInfo){
        $info = array();
        $info['fields']     = array();
        $info['fields_rev'] = array();

        static::registerModelMetaClass($info, $class, $classInfo);

        /** read properties */
        $idDefined = false;
        foreach($propertiesInfo as $nm => $property){
            if (String::startsWith($nm, '__')) continue;
            if ($property->has('ignore')) continue;

            $info['fields'][$nm] = 1;

            $cur = array();
            static::registerModelMetaProperty($cur, $class, $nm, $property);

            // indexed
            if ( $property->has('indexed') ){
                $indexed = $property->get('indexed');
                $index = array(
                    'options' => array(),
                    'fields' => array($cur['column'] => $indexed->get('sort', 0))
                );

                //foreach($indexed->getKeys() as $key){
                static::registerModelMetaIndex($index, $info, $classInfo, $nm, $indexed);
                //}

                $info['indexed'][] = $index;
            }

            if ( $property->has('id') ){
                if ( $idDefined ){
                    throw new AnnotationException($property, 'id', 'Can\'t redeclare id field');
                }

                static::registerModelMetaId($cur, $info, $class, $nm, $property);
                $idDefined = true;
            }

            $info['fields_rev'][$cur['column']] = $nm;
            $info['fields'][$nm] = $cur;
        }

        /** all indexes */
        $anIndexed = $classInfo->getAsArray('indexed');

        foreach($anIndexed as $indexed){
            $cur = array('options'=>array(), 'fields'=>array());
            foreach($indexed->getKeys() as $key){
                if ($key[0] == '$') continue;
                if (!$info['fields'][$key])
                    throw new AnnotationException($classInfo, 'indexed',
                        String::format('Can\'t find `%s` field for index', $key));

                $column = $info['fields'][$key]['column'];
                $cur['fields'][$column] = $indexed->get($key);
            }
            static::registerModelMetaIndex($cur, $info, $classInfo, null, $indexed);
            $info['indexed'][] = $cur;
        }
        self::$modelInfo[ $class->getName() ] = $info;
    }

    /**
     * @param string $className
     */
    public function registerModel($className){
        if ( $className === AbstractActiveRecord::type ) return;

        /** @var $annotation Annotations */
        $class     = new \ReflectionClass($className);
        $classInfo = Annotations::getClassAnnotation($class);
        $propertiesInfo = array();

        foreach($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property){
            if ( $property->isStatic() ) continue;

            $propertiesInfo[$property->getName()] = Annotations::getPropertyAnnotation($property);
        }

        static::registerModelMeta($class, $classInfo, $propertiesInfo);
    }

    /****** UTILS *******/
    public function typed($value, $fieldMeta){
        return $value; // TODO
    }

    public function typedFetch($value, $fieldMeta){
        return $value;
    }
}

abstract class ActiveRecordCursor implements \Iterator {

    /**
     * @param array $fields
     * @return $this
     */
    abstract public function sort(array $fields);

    /**
     * @param int $value
     * @return $this
     */
    abstract public function skip($value);

    /**
     * @param int $value
     * @return $this
     */
    abstract public function limit($value);

    /**
     * @return int
     */
    abstract public function count();

    /**
     * @return mixed
     */
    abstract public function explain();

    /**
     * @return AbstractActiveRecord
     */
    public function first(){
        $this->rewind();
        return $this->current();
    }

    /**
     * @return AbstractActiveRecord
     */
    abstract public function firstOrCreate();

    /**
     * @return AbstractActiveRecord[]
     */
    public function asArray(){
        return iterator_to_array($this);
    }
}


class QueryException extends CoreException {

    public function __construct($message){
        parent::__construct('Query build error: ' . $message);
    }
}

abstract class AbstractQuery {

    /** @var AbstractService */
    protected $service;
    protected $meta;

    protected $data;
    protected $stackField;

    public function __construct(AbstractService $service){
        $this->data    = array();
        $this->service = $service;
        $this->meta    = $service->getMeta();
    }

    public function field($name){
        if ($this->stackField)
            throw QueryException::formated('field `%s` set already', $this->stackField);

        if (!($meta = $this->meta['fields'][$name]))
            throw QueryException::formated('field `%s` not exists in `%s` document type', $name, $this->service->getModelClass());

        $this->stackField = $name;
        return $meta['column'];
    }

    protected function popField(){
        $name = $this->stackField;
        $this->stackField = '';
        if (!$name){
            throw QueryException::formated('field is not set');
        }

        $column = $this->meta['fields'][$name]['column'];
        return $column;
    }

    protected function getValue($field, $value){
        $info = $this->meta['fields'][$field];
        if (is_array($value)){
            foreach($value as &$el){
                $el = $this->service->typed($el, $info);
            }
            return $value;
        } else {

            return $this->service->typed($value, $info);
        }
    }

    protected function addOR(AbstractQuery $query){
        $this->data['$or'][] = $query->getData();
        return $this;
    }

    protected function addAND(AbstractQuery $query){
        $this->data['$and'][] = $query->getData();
        return $this;
    }

    /**
     * @param string|AbstractQuery $whereOrQuery
     * @return $this
     */
    public function _OR($whereOrQuery){
        if ($whereOrQuery instanceof AbstractQuery)
            return $this->addOR($whereOrQuery);

        $query = clone $this;
        $query->clear();
        call_user_func_array(array($query, 'filter'), func_get_args());

        return $this->addOR($query);
    }

    /**
     * @param string|AbstractQuery $whereOrQuery
     * @return $this
     */
    public function _AND($whereOrQuery){
        if ($whereOrQuery instanceof AbstractQuery)
            return $this->addAND($whereOrQuery);

        $query = clone $this;
        $query->clear();
        call_user_func_array(array($query, 'filter'), func_get_args());

        return $this->addAND($query);
    }

    public function clear(){
        $this->data = array();
    }

    protected function popValue($field, $value, $prefix = '$eq', $typed = true){
        $column = $this->field($field);

        if ($prefix == '$eq'){
            $this->data[$column] = $typed ? $this->getValue($field, $value) : $value;
        } else
            $this->data[$column][$prefix] = $typed ? $this->getValue($field, $value) : $value;

        return $this;
    }

    protected function filterCustomOperator($field, $value, $operator){
        throw QueryException::formated('unknown filter operator - "%s %s"', $field, $operator);
    }

    /**
     * Example: ->filter('name', "my name", age >', 18);
     *
     * @param mixed[] values ...
     * @throws static
     * @return $this
     */
    public function filter(){
        $values = func_get_args();
        if (sizeof($values) % 2 !== 0)
            throw QueryException::formated('number of fields and values ​​are not the same');

        $field = '';
        foreach($values as $i => $value){
            if ($i % 2){
                $field    = explode(' ', $field, 2);
                $operator = trim($field[1]);
                $field    = $field[0];

                switch($operator){
                    case '':
                    case '=': $this->eq($field, $value); break;

                    case '!=':
                    case '<>': $this->notEq($field, $value); break;

                    case '>': $this->gt($field, $value); break;
                    case '<': $this->lt($field, $value); break;
                    case '>=': $this->gte($field, $value); break;
                    case '<=': $this->lte($field, $value); break;
                    case 'in': $this->in($field, $value); break;
                    case 'nin': $this->notIn($field, $value); break;
                    case '%':
                    case 'like': $this->like($field, $value); break;
                    default: {
                        $this->filterCustomOperator($field, $value, $operator);
                    }
                }
            } else {
                $field = $value;
            }
        }

        return $this;
    }

    public function eq($field, $value){
        return $this->popValue($field, $value);
    }

    public function notEq($field, $value){
        return $this->popValue($field, $value, '$ne');
    }

    public function gt($field, $value){
        return $this->popValue($field, $value, '$gt');
    }

    public function gte($field, $value){
        return $this->popValue($field, $value, '$gte');
    }

    public function lt($field, $value){
        return $this->popValue($field, $value, '$lt');
    }

    public function lte($field, $value){
        return $this->popValue($field, $value, '$lte');
    }

    public function all($field, array $value){
        return $this->popValue($field, $value, '$all', false);
    }

    public function in($field, array $value){
        return $this->popValue($field, $value, '$in');
    }

    public function notIn($field, array $value){
        return $this->popValue($field, $value, '$nin');
    }

    public function like($field, $expr){
        return $this->popValue($field, $expr, '$like', false);
    }

    public function notLike($field, $expr){
        return $this->popValue($field, $expr, '$nlike', false);
    }

    public function isNull($field){
        return $this->popValue($field, true, '$null', false);
    }

    public function isNotNull($field){
        return $this->popValue($field, true, '$nnull', false);
    }

    public function getData(){
        return $this->data;
    }
}