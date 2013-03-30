<?php
namespace modules\mongodb;

use framework\exceptions\CoreException;
use framework\lang\ArrayTyped;
use framework\lang\ClassLoader;
use framework\lang\String;
use framework\mvc\AbstractModel;
use framework\mvc\AbstractService;
use framework\mvc\Annotations;
use framework\mvc\IHandleAfterLoad;
use framework\mvc\IHandleBeforeLoad;

class Service extends AbstractService {

    /** @var \MongoCollection */
    protected $collection;

    protected function __construct($modelClass){
        parent::__construct($modelClass);
        $this->collection = Module::$db->selectCollection( $this->meta['collection'] );
    }

    /**
     * @param mixed $id
     * @param array $fields
     * @param bool $lazy
     * @return array
     */
    protected function findDataById($id, array $fields = array(), $lazy = false){
        return $this->collection->findOne(array('_id' => $id), $fields);
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
     * upsert operation in mongodb
     * @param AbstractModel $document
     * @param array $options
     * @return array|bool
     */
    public function save(AbstractModel $document, array $options = array()){

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


    public function remove(AbstractModel $object, array $options = array()){

        $id = $this->getId($object);
        if ( $id !== null ){
            $this->collection->remove(array('_id' => $id), $options);
            $object->setId(null);
        }
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

                        if ( !is_a($value, $type) && !is_subclass_of($value, $type) ){

                            return $value;
                            /*throw CoreException::formated('`%s` is not instance of %s class',
                                is_scalar($value) || is_object($value) ? (string)$value : gettype($value),
                                $type);*/
                        } else {

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