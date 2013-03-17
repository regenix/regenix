<?php
namespace modules\mongodb;

use framework\exceptions\CoreException;
use framework\lang\ClassLoader;
use framework\lang\String;

class Service {

    /** @var string */
    private $modelClass;

    /** @var array */
    protected $meta;

    /** @var \MongoCollection */
    protected $collection;

    protected function __construct($modelClass){
        $this->meta = Module::getModelInfo($modelClass);
        $this->collection = Module::$db->selectCollection( $this->meta['collection'] );
        $this->modelClass = $modelClass;
    }

    /**
     * @return array
     */
    public function getMeta(){
        return $this->meta;
    }

    /**
     * @var Service[]
     */
    private static $services = array();

    /**
     * @param $modelClass
     * @return Service
     */
    public static function get($modelClass){
        if ( $result = self::$services[$modelClass] )
            return $result;

        return self::$services[$modelClass] = new Service($modelClass);
    }

    protected function loadObject(Document $object, $data, $lazy = false){

        if ( $object instanceof IHandleBeforeLoad ){
            $object->onBeforeLoad($data);
        }

        foreach($data as $column => $value){
            if ( $column === '_id' ){
                self::setId($object, $value);
            } else {
                $field = $this->meta['fields_rev'][ $column ];
                if ( $field ){
                    self::__callSetter($object, $field, $value, $lazy);
                }
            }
        }

        if ( $object instanceof IHandleAfterLoad ){
            $object->onAfterLoad();
        }

        return $object;
    }

    public function __callGetter(Document $object, $field){

        $info = $this->meta['fields'][$field];
        $value = $object->__data[$field];

        if ( $info['ref'] && $info['ref']['lazy'] ){

            if ( $info['is_array'] ){

            } else {

                $type = $info['type'];
                ClassLoader::load($type);
                $service = Service::get($type);

                if ( is_scalar($value) || ($isRef = \MongoDBRef::isRef($value)) || ($value instanceof \MongoId) ){
                    if ( $isRef )
                        $value = $value['$id'];

                    $value = $service->findById($value);
                    $this->__callSetter($object, $field, $value);
                }
            }
        }

        return $value;
    }

    public function __callSetter(Document $object, $field, $value, $lazy = false){

        $info = $this->meta['fields'][$field];

        if ( $info['ref'] && !$info['ref']['lazy'] && $lazy === false){
            ClassLoader::load($info['type']);
            $service = Service::get($info['type']);
            $value   = $service->findById($value['$id'] ? $value['$id'] : $value, array(), true);
        }

        if ($info['setter']){
            $method = 'set' . $field;
            $object->{$method}($value);
        } else {
            $object->__data[$field] = $value;
        }
    }

    /**
     * @param Document $object
     * @param bool $typed
     * @return mixed|null
     */
    public function getId(Document $object, $typed = true){
        $idField = $this->meta['id_field']['field'];
        if ( $idField ){

            return $typed
                ? self::typed($object->__data[$idField], $this->meta['id_field']['type'])
                : $object->__data[$idField];
        } else
            return null;
    }

    /**
     * @param Document $object
     * @param $id
     */
    public function setId(Document $object, $id){

        $idField = $this->meta['id_field'];
        if ( $idField ){
            $field = $idField['field'];
            $object->__data[$field] = self::typed($id, $idField['type']);
        } else
            CoreException::formated('Document `%s` has no @id field', $this->meta['name']);
    }

    /**
     * @param Document $document
     * @param bool $operation
     * @param bool $skipId
     * @param bool $isNew
     * @return array
     */
    protected function getData(Document $document, $operation = false, $skipId = false, $isNew = false){

        $meta  = $this->meta;
        $data  = array();

        foreach($meta['fields'] as $field => $info){
            if ( $skipId && $info['column'] == '_id' ) continue;

            $value = self::typed(self::__callGetter($document, $field), $info['type'], $info['ref']);
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
     * @param mixed $id
     * @param array $fields
     * @return Document
     */
    public function findById($id, array $fields = array(), $lazy = false){

        $idField = $this->meta['id_field'];
        $data    = $this->collection->findOne(array('_id' => self::typed($id, $idField['type'])), $fields);

        if ( $data === null )
            return null;

        $modelClass = $this->modelClass;
        return $this->loadObject(new $modelClass(), $data, $lazy);
    }

    /**
     * @param Document $document
     * @param array $fields
     * @param array $fields
     * @throws \framework\exceptions\CoreException
     * @return bool
     */
    public function reload(Document $document, array $fields = array()){

        $id = $this->getId($document);
        if ( !$id )
            throw CoreException::formated('Can`t reload non-exist document');

        $data = $this->collection->findOne(array('_id' => $id), $fields);
        if ( $data ){
            $this->loadObject($document, $data);
            return true;
        } else
            return false;
    }

    /**
     * upsert operation in mongodb
     * @param Document $document
     * @param array $options
     * @return array|bool
     */
    public function save(Document $document, array $options = array()){

        if ( $document->isNew() ){

            $data   = $this->getData($document, false, false, true);
            $atomic = $data['$atomic'];
            unset($data['$atomic']);
            unset($data['$unset']);

            $result = $this->collection->insert($data);
            $this->setId($document, $data['_id']);

            if ( $atomic != null ){
                foreach($atomic as $key => $el){
                    $this->__callSetter($document, $key, $el);
                }
            }

        } else {
            $data   = $this->getData($document, '$set', true);
            $result = $this->collection->update(array('_id' => $this->getId($document)), $data, $options );
            if ($data['$inc'] || $data['$unset']){
                $this->reload($document);
            }
        }

        return $result;
    }

    public function saveAtomic(Document $document){

        $data = $this->getData($document, '$set');
        $atomicData = $data;
        unset($data['$set']);

        //$result = $this->collection->update(array())
    }

    /**
     * only set fields, $set operation in mongodb
     * @param Document $document
     * @param array $options
     * @throws \framework\exceptions\CoreException
     * @return bool
     */
    public function set(Document $document, array $options = array()){

        $id = $this->getId($document);
        if ( $id === null )
            throw CoreException::formated('Can\'t use set() method for not saved document');

        $data   = $this->getData($document, '$set', true);
        $result = $this->collection->update(array('_id' => $id), $data, $options);

        if ($data['$inc'] || $data['$push'] || $data['$pushAll']){
            $this->reload($document);
        }

        return $result;
    }

    /**
     * @param Document $document
     * @param array $options
     * @return bool
     * @throws \framework\exceptions\CoreException
     */
    public function setOnlyAtomic(Document $document, array $options = array()){

        $id = $this->getId($document);
        if ( $id === null )
            throw CoreException::formated('Can\'t use setOnlyAtomic() method for not saved document');


        $data   = $this->getData($document, '$set', true);
        unset($data['$set']);

        if ( sizeof($data) ){

            $result = $this->collection->update(array('_id' => $id), $data, $options);
            $this->reload($document);
            return $result;
        } else
            return true;
    }

    /**
     * @param Document[] $documents
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
     * @param Document[] $documents
     * @param array $options
     * @return array of bool
     */
    public function setAll(array $documents, array $options = array()){

        $result = array();
        foreach($documents as $document){
            $result[] = $this->set($document, $options);
        }
        return $result;
    }


    /****** UTILS *******/
    public static function typed($value, $type, $ref = null){

        if ( $value === null )
            return null;

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

            case 'date': return $value instanceof \MongoDate ? $value : new \MongoDate( (int)$value );

            case 'oid':
            case 'MongoId':
            case '\MongoId':
            case 'ObjectId': {

                return $value instanceof \MongoId ? $value : new \MongoId( $value );
            }

            case 'timestamp': return $value instanceof \MongoTimestamp ? $value : new \MongoTimestamp();

            case 'code': return $value instanceof \MongoCode ? $value : new \MongoCode($value);

            case 'double':
            case 'float': return (double)$value;

            case 'array': return (array)$value;

            default: {

                if ( String::endsWith($type, '[]') ){

                    $realType = substr($type, 0, -2);
                    $value = (array)$value;

                    foreach($value as &$val){
                        $val = self::typed($val, $realType, $ref);
                    }

                    unset($val);
                    return $value;

                } else {

                    if ( $ref ){

                        if ( $type !== get_class($value) && !is_subclass_of($value, $type) ){
                            throw CoreException::formated('`%s` is not instance of %s class',
                                is_scalar($value) ? (string)$value : get_class($value),
                                $type);
                        }

                        ClassLoader::load($type);
                        $info = Module::getModelInfo($type);
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

        return $value;
    }
}