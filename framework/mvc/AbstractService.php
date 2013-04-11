<?php
/**
 * Author: Dmitriy Zayceff
 * E-mail: dz@dim-s.net
 * Date: 17.03.13
 */

namespace framework\mvc;

use framework\StrictObject;
use framework\exceptions\AnnotationException;
use framework\exceptions\CoreException;
use framework\lang\ArrayTyped;
use framework\lang\ClassLoader;
use framework\lang\String;

abstract class AbstractService extends StrictObject {

    /** @var array */
    protected static $modelInfo = array();

    /** @var string */
    protected $modelClass;

    /** @var array */
    protected $meta;

    /**
     * @param $modelClass string
     */
    protected function __construct($modelClass){
        $this->meta = self::$modelInfo[$modelClass];
        $this->modelClass = $modelClass;
    }

    /**
     * @return array
     */
    public function getMeta(){
        return $this->meta;
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

    /**
     * @param $modelClass
     * @return AbstractService
     */
    public static function get($modelClass){
        if ( $result = self::$services[$modelClass] )
            return $result;

        return self::$services[$modelClass] = static::newInstance($modelClass);
    }

    abstract public function save(AbstractModel $object, array $options = array());
    abstract public function remove(AbstractModel $object, array $options = array());

    /**
     * @param AbstractModel[] $documents
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

    protected function fetch(AbstractModel $object, $data, $lazyNeed = false){
        if ( $object instanceof IHandleBeforeLoad ){
            $object->onBeforeLoad($data);
        }

        foreach($data as $column => $value){
            $field = $this->meta['fields_rev'][ $column ];
            if ( $field ){
                $this->__callSetter($object, $field, $value, $lazyNeed);
            }
        }

        if ( $object instanceof IHandleAfterLoad ){
            $object->onAfterLoad();
        }

        return $object;
    }

    /**
     * @param AbstractModel $object
     * @param bool $typed
     * @return mixed|null
     */
    public function getId(AbstractModel $object, $typed = true){
        $idField = $this->meta['id_field']['field'];
        if ( $idField ){
            return $typed
                ? static::typed($object->__data[$idField], $this->meta['id_field']['type'])
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
     * @return AbstractModel
     */
    public function findById($id, array $fields = array(), $lazy = false){
        $idField = $this->meta['id_field'];
        $data  = $this->findDataById(static::typed($id, $idField['type']));
        if ( $data === null )
            return null;

        $modelClass = $this->modelClass;
        return $this->fetch(new $modelClass(), $data, $lazy);
    }

    /**
     * @param mixed $value
     * @param array $fields
     * @param bool $lazy
     * @return AbstractModel
     */
    protected function findByRef($value, array $fields = array(), $lazy = false){
        return $this->findById($value, $fields, $lazy);
    }

    /**
     * @param AbstractModel $object
     * @param array $fields
     * @return bool
     * @throws \framework\exceptions\CoreException
     */
    public function reload(AbstractModel $object, array $fields = array()){
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

    public function __callGetter(AbstractModel $object, $field){
        $info  = $this->meta['fields'][$field];
        $value = $object->__data[$field];

        if ( $info['ref'] && $info['ref']['lazy'] ){

            if ( $info['is_array'] ){

            } else {

                $type = $info['type'];
                ClassLoader::load($type);

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

    public function __callSetter(AbstractModel $object, $field, $value, $lazy = false){
        $info = $this->meta['fields'][$field];

        if ( $info['ref'] && !$info['ref']['lazy'] && $lazy === false){
            ClassLoader::load($info['type']);
            $type = $info['type'];
            /** @var $service AbstractService */
            $service = $type::getService();
            $value   = $service->findByRef($value, array(), true);
        }

        if ($info['setter']){
            $method = 'set' . $field;
            $object->{$method}($value);
        } else {
            $object->__data[$field] = $value;
        }
    }


    /**
     * @param AbstractModel $object
     * @param $id
     */
    public function setId(AbstractModel $object, $id){
        $idField = $this->meta['id_field'];
        if ( $idField ){
            $field = $idField['field'];
            $object->__data[$field] = static::typed($id, $idField['type']);
        } else
            CoreException::formated('Document `%s` has no @id field', $this->meta['name']);
    }

    protected static function registerModelMetaClass(&$info, \ReflectionClass $class, Annotations $classInfo){
        $name = $class->getName();
        $anCollection = $classInfo->get('collection');

        $info['collection'] = $anCollection->getDefault( $name );
    }

    protected static function registerModelMetaId(&$propInfo, &$allInfo,
                                                  \ReflectionClass $class, $name, Annotations $property){
        $allInfo['id_field'] = array(
            'column' => $propInfo['column'], 'field' => $propInfo['name'], 'type' => $propInfo['type']
        );
    }

    protected static function registerModelMetaProperty(&$cur, \ReflectionClass $class, $name, Annotations $property){
        $cur['name']   = $name;
        $cur['column'] = $property->get('column')->getDefault( $name );
        $cur['type']   = $property->get('var')->getDefault('mixed');

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
     * @throws \framework\exceptions\AnnotationException
     */
    protected static function registerModelMetaIndex(&$infoIndex, &$allInfo, Annotations $classInfo, $key, ArrayTyped $indexed){
        if ( $key[0] === '$' ) return;

        $column = $allInfo['fields'][ $key ];
        if ( !$column ){
            throw new AnnotationException($classInfo, 'indexed',
                String::format('Can\'t find `%s` field for index', $key));
        }
        $infoIndex['fields'][$column['column']] = $indexed->get($key, 0);
    }

    /**
     * @param \ReflectionClass $class
     * @param Annotations $classInfo
     * @param Annotations[] $propertiesInfo
     * @throws \framework\exceptions\AnnotationException
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

            $info['fields'][$nm] = 1;

            $cur = array();
            static::registerModelMetaProperty($cur, $class, $nm, $property);

            // indexed
            if ( $property->has('indexed') ){
                $indexed = $property->get('indexed');
                $index = array(
                    'options' => array(),
                    'fields' => array($cur['column'] => $indexed->getInteger('sort', 0))
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
        $anIndexed    = $classInfo->getAsArray('indexed');

        foreach($anIndexed as $indexed){
            $cur = array('options'=>array(), 'fields'=>array());
            foreach($indexed->getKeys() as $key){
                static::registerModelMetaIndex($cur, $info, $classInfo, $key, $indexed);
            }
            $info['indexed'][] = $cur;
        }
        self::$modelInfo[ $class->getName() ] = $info;
    }

    /**
     * @param string $className
     */
    public static function registerModel($className){
        if ( $className === AbstractModel::type ) return;

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
    public static function typed($value, $type, $ref = null){
        return $value; // TODO
    }
}