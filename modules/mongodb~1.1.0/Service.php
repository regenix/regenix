<?php
namespace modules\mongodb;

use framework\exceptions\CoreException;
use framework\lang\ArrayTyped;
use framework\lang\ClassLoader;
use framework\lang\String;
use framework\libs\Time;
use framework\mvc\AbstractQuery;
use framework\mvc\AbstractService;
use framework\mvc\AbstractActiveRecord;
use framework\mvc\ActiveRecordCursor;
use framework\mvc\Annotations;
use framework\mvc\IHandleAfterLoad;
use framework\mvc\IHandleAfterRemove;
use framework\mvc\IHandleAfterSave;
use framework\mvc\IHandleBeforeLoad;
use framework\mvc\IHandleBeforeRemove;
use framework\mvc\IHandleBeforeSave;
use framework\mvc\TActiveRecordHandle;

class Service extends AbstractService {

    /** @var \MongoCollection */
    private $collection_;

    protected function __construct($modelClass){
        parent::__construct($modelClass);
    }

    protected function getCollection(){
        if ($this->collection_)
            return $this->collection_;

        $meta = $this->getMeta();
        return $this->collection_ = Module::$db->selectCollection( $meta['collection'] );
    }

    protected function findDataById($id, array $fields = array(), $lazy = false){
        return $this->getCollection()->findOne(array('_id' => $id), $fields);
    }

    protected function findByRef($value, array $fields = array(), $lazy = false){
        if (\MongoDBRef::isRef($value))
            $value = $value['$id'];

        return parent::findByRef($value, $fields, $lazy);
    }

    protected function isReference($value){
        return parent::isReference($value) || \MongoDBRef::isRef($value);
    }

    /**
     * @param AbstractQuery $query
     * @param array $fields
     * @param bool $lazy
     * @return DocumentCursor
     */
    public function findByFilter(AbstractQuery $query, array $fields = array(), $lazy = false){
        return new DocumentCursor($this->getCollection()->find($query ? $query->getData() : array(), $fields), $this, $lazy);
    }

    /**
     * TODO optimize
     * @param AbstractQuery $query
     * @param array $update
     * @param array $fields
     * @param bool $lazy
     * @return DocumentCursor
     */
    public function findByFilterAndModify(AbstractQuery $query, array $update, array $fields = array(), $lazy = false){
        return new DocumentCursor($this->getCollection()->findAndModify($query ? $query->getData() : array(), $update, $fields), $this, $lazy);
    }

    /**
     * @param ActiveRecord $document
     * @param bool $operation
     * @param bool $skipId
     * @param bool $isNew
     * @return array
     */
    protected function getData(ActiveRecord $document, $operation = false, $skipId = false, $isNew = false){
        $meta  = $this->getMeta();
        $data  = array();

        foreach($meta['fields'] as $field => $info){
            if ( $skipId && $info['column'] == '_id' ) continue;
            //if ( $operation === '$set' && !$document->__modified[$field] ) continue;

            $value = $this->typed($this->__callGetter($document, $field), $info);

            if ( $value !== null ){
                if ( $value instanceof AtomicOperation ){
                    if ( $isNew ){
                        $def = $value->getDefaultValue();
                        if ( $def !== null ){
                            $data[ $operation ][ $info['column'] ] = $def;
                            $data['$atomic'][ $field ] = $def;
                        }

                    } else {
                        $data[ $value->oper ][ $info['column'] ] = $value->value;
                    }

                    $document->__data[$field] = null;
                } else {
                    if ( $operation )
                        $data[ $operation ][ $info['column'] ] = $value;
                    else
                        $data[ $info['column'] ] = $value;
                }
            } else {
                $data['$unset'][ $info['column'] ] = '';
            }
        }
        return $data;
    }

    /**
     * upsert operation in mongodb
     * @param AbstractActiveRecord $document
     * @param array $options
     * @return array|bool
     */
    public function save(AbstractActiveRecord $document, array $options = array()){
        $isNew = $document->isNew();

        $class = $this->getModelClass();
        $class::callHandle('beforeSave', $document, $isNew);

        if ($document instanceof IHandleBeforeSave){
            $document->onBeforeSave($isNew);
        }

        if ( $isNew ){
            $data   = $this->getData($document, false, false, true);
            $atomic = $data['$atomic'];
            unset($data['$atomic']);
            unset($data['$unset']);

            $result = $this->getCollection()->save($data);
            $this->setId($document, $data['_id']);

            if ( $atomic != null ){
                foreach($atomic as $key => $el){
                    $this->__callSetter($document, $key, $el);
                }
            }
        } else {
            $data   = $this->getData($document, '$set', true);

            if ($data){
                $result = $this->getCollection()->update(array('_id' => $this->getId($document)), $data, $options );

                if ($data['$inc'] || $data['$unset']){
                    $this->reload($document);
                }
            }
        }

        $class::callHandle('afterSave', $document, $isNew);
        if ($document instanceof IHandleAfterSave)
            $document->onAfterSave($isNew);

        $document->__fetched = true;

        return $result;
    }

    public function remove(AbstractActiveRecord $object, array $options = array()){
        $id = $this->getId($object);
        if ( $id !== null ){
            $class = $this->getModelClass();
            $class::callHandle('beforeRemove', $object);
            if ($object instanceof IHandleBeforeRemove)
                $object->onBeforeRemove();

            $this->getCollection()->remove(array('_id' => $id), $options);
            $object->setId(null);

            $class::callHandle('afterRemove', $object);
            if ($object instanceof IHandleAfterRemove){
                $object->onAfterRemove();
            }
        }
    }

    /****** UTILS *******/
    public function typedFetch($value, $fieldMeta){
        if ($value instanceof \MongoDate || $value instanceof \MongoTimestamp){
            $return = new \DateTime();
            $return->setTimestamp($value->sec);
            return $return;
        }

        if ($value instanceof \MongoId){
            return (string)$value;
        }

        return parent::typedFetch($value, $fieldMeta);
    }

    public function typed($value, $fieldMeta){
        $type = $fieldMeta['type'];
        $ref  = $fieldMeta['ref'];

        if ( $value === null ){
            // auto values
            switch($fieldMeta['timestamp']){
                case 'timestamp': return new \MongoTimestamp();
            }
            return null;
        }

        if ( $value instanceof AtomicOperation ){
            if (!$value->validateType($type))
                throw CoreException::formated('Can\'t use `%s` atomic operation for `%s` type', $value->oper, $type);

            if ( $value->needTyped ){
                $value->doTyped($type, $ref);
            }

            return $value;
        }

        switch($type){
            case 'string': return (string)$value;

            case 'blob': return $value instanceof \MongoBinData ? $value : new \MongoBinData($value);

            case 'int':
            case 'integer': return $value instanceof \MongoInt32 ? $value : new \MongoInt32($value);

            case 'bool':
            case 'boolean': return (boolean)$value;

            case 'long': return $value instanceof \MongoInt64 ? $value : new \MongoInt64($value);

            case '\MongoDate':
            case 'MongoDate':
            case '\DateTime':
            case 'DateTime':
            case 'date': {
                if ($fieldMeta['timestamp'])
                    return new \MongoTimestamp();

                if ($value instanceof \DateTime){
                    $tz = $value->getTimezone();
                    $value->setTimezone(\DateTimeZone::UTC);
                    $ret = new \MongoDate($value->getTimestamp());
                    $value->setTimezone($tz);
                    return $ret;
                } else
                    return $value instanceof \MongoDate ? $value : new \MongoDate( (int)$value );
            }

            case 'oid':
            case 'MongoId':
            case '\MongoId':
            case 'ObjectId': {
                return $value instanceof \MongoId ? $value : new \MongoId( $value );
            }

            case 'code': return $value instanceof \MongoCode ? $value : new \MongoCode($value);

            case 'double':
            case 'float': return (double)$value;

            case 'array': return (array)$value;

            default: {

                if ( String::endsWith($type, '[]') ){

                    $realType = substr($type, 0, -2);
                    $value = (array)$value;

                    foreach($value as &$val){
                        $val = $this->typed($val, array('type' => $realType, 'ref' => $ref));
                    }

                    unset($val);
                    return $value;

                } else {

                    if ( $ref ){

                        if ( !is_a($value, $type) && !is_subclass_of($value, $type) ){

                            return $value;
                            /*throw CoreException::formated('`%s` is not instance of %s class',
                                is_scalar($value) || is_object($value) ? (string)$value : gettype($value),
                                $type);*/
                        } else {

                            ClassLoader::load($type);
                            $info = self::$modelInfo[$type];
                            if ( !$info ){
                                throw CoreException::formated('`%s.class` is not document class for mongo $ref', $type);
                            }

                            if ( $ref['small'] ){
                                return $value === null ? null : $value->getId();
                            } else {

                                $link = $value === null
                                    ? null
                                    : \MongoDBRef::create($info['collection'], $value->getId());

                                return $link;
                            }
                        }
                    }
                }
            }
        }

        return $value;
    }

    /**
     * @param $modelClass
     * @return AbstractService
     */
    protected static function newInstance($modelClass){
        return new Service($modelClass);
    }

    /**
     * @param array $info
     * @param $allInfo
     * @param Annotations $classInfo
     * @param string $key
     * @param ArrayTyped $indexed
     */
    protected static function registerModelMetaIndex(&$info, &$allInfo, Annotations $classInfo, $key, ArrayTyped $indexed){
        if (in_array($info, array('$background', '$unique', '$dropDups', '$sparse'), true))
            $info['options'][substr($key,1)] = true;
        elseif ( $key === '$expire' ){
            $info['options']['expireAfterSeconds'] = $indexed->getInteger($key, 0);
        } elseif ( $key === '$w' ){
            $val = $indexed->get($key);
            if ( is_numeric($val) ) $val = (int)$val;

            $info['options']['w'] = $val;
        } else
            parent::registerModelMetaIndex($info, $allInfo, $classInfo, $key, $indexed);
    }

    /**
     * @param $propInfo
     * @param $allInfo
     * @param \ReflectionClass $class
     * @param $name
     * @param Annotations $property
     */
    protected static function registerModelMetaId(&$propInfo, &$allInfo,
                                                  \ReflectionClass $class, $name, Annotations $property){
        $propInfo['column'] = '_id';
        parent::registerModelMetaId($propInfo, $allInfo, $class, $name, $property);
    }
}


class DocumentCursor extends ActiveRecordCursor {

    /** @var \MongoCursor */
    private $cursor;

    /** @var AbstractService */
    private $service;

    /** @var bool */
    private $lazy;

    private $pos = 0;

    public function __construct(\MongoCursor $cursor, AbstractService $service, $lazy = false){
        $this->cursor  = $cursor;
        $this->service = $service;
        $this->lazy    = $lazy;
    }

    /**
     * @param string|int $time
     * @return $this
     */
    public function timeout($time){
        $time = is_string($time) ? Time::parseDuration($time) * 1000 : (int)$time;
        $this->cursor->timeout($time);
        return $this;
    }

    /**
     * @return $this
     */
    public function snapshot(){
        $this->cursor->snapshot();
        return $this;
    }

    public function sort(array $fields){
        $this->cursor->sort($fields);
        return $this;
    }

    public function skip($value){
        $this->cursor->skip($value);
        return $this;
    }

    public function limit($value){
        $this->cursor->limit($value);
        return $this;
    }

    public function count(){
        return $this->cursor->count();
    }

    public function explain(){
        return $this->cursor->explain();
    }

    /**
     * @return ActiveRecord
     */
    public function current() {
        $modelClass = $this->service->getModelClass();
        $data = $this->cursor->current();
        if ($data === null)
            return null;

        return $this->service->fetch(new $modelClass, $data, $this->lazy);
    }

    public function next() {
        $this->cursor->next();
        $this->pos++;
    }

    public function key() {
        return $this->pos;
    }

    public function valid() {
        return $this->cursor->valid();
    }

    public function rewind() {
        $this->cursor->rewind();
        $this->pos = 0;
    }

    /**
     * @return ActiveRecord[]
     */
    public function asArray(){
        return parent::asArray();
    }
}


class Query extends AbstractQuery {

    /**
     * @param bool $value
     * @return $this
     */
    public function exists($value){
        $this->data[$this->popField()]['$exists'] = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getData(){
        $eq = $this->data['$eq'];
        $data = array_merge($this->data, $eq);
        unset($data['$eq']);
        return $data;
    }

    /**
     * @param string $pattern
     * @param string $flags
     * @return $this
     */
    public function pattern($pattern, $flags = ''){
        $regex = \MongoRegex($pattern);
        if ($flags)
            $regex->flags = $flags;
        $this->data[$this->popField()] = $regex;
        return $this;
    }
}