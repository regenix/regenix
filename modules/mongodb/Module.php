<?php
namespace modules\mongodb;

use framework\Core;
use framework\Project;
use framework\SDK;
use framework\exceptions\AnnotationException;
use framework\lang\String;
use framework\modules\AbstractModule;
use framework\exceptions\CoreException;
use framework\mvc\Annotations;

class Module extends AbstractModule {

    const type = __CLASS__;
    private static $modelInfo = array();

    /** @var \MongoDB */
    public static $db;

    /** @var \MongoClient */
    public static $client;

    public function getName() {
        return 'MongoDB';
    }

    private $init = false;

    public function initConnection(){

        if ( $this->init ) return;

        $this->init = true;
        $project = Project::current();
        $config = $project->config;

        $dbHost = 'mongodb://' . $config->getString('mongodb.host', 'localhost:27017');
        $dbName = $config->get('mongodb.dbname', 'regenix');
        $dbUser = $config->get('mongodb.user');
        $dbPass = $config->get('mongodb.password');
        $dbW    = $config->get('mongodb.writeConcern', 1);
        $dbTimeout = $config->getNumber('mongodb.timeout', 0);
        $dbWTimeout = $config->getNumber('mongodb.wTimeout', 0);
        $dbReplicaSet = $config->get('mongodb.replicaSet');

        $options = array('connect' => true, 'w' => $dbW);

        if ( $dbUser ){
            $options['db']       = $dbName;
            $options['password'] = $dbPass;
            $options['username'] = $dbUser;
        }

        if ( $dbTimeout )
            $options['connectTimeoutMS'] = $dbTimeout;

        if ( $dbReplicaSet )
            $options['replicaSet'] = $dbReplicaSet;

        if ( $dbWTimeout )
            $options['wTimeout'] = $dbWTimeout;

        self::$client = new \MongoClient($dbHost, $options);
        self::$db     = self::$client->selectDB( $dbName );

        $this->registerAnnotations();
    }

    public function __construct(){
        
        if ( !extension_loaded( 'mongo' ) ){
            throw CoreException::formated('Unable to load `php_mongodb` extension, please install it!');
        }
        //SDK::addAfterModelLoad(array($this, 'onAfterModelLoad'));
    }


    private function registerAnnotations(){

        // @collection annotation
        Annotations::registerAnnotation('collection', array(
            'fields' => array('_arg' => 'string'),
            'require' => array('_arg')
        ), 'class');

        // @indexed .class
        Annotations::registerAnnotation('indexed', array(
            'fields' => array('$background' => 'boolean'),
            'multi' => true,
            'any' => true
        ), 'class');

        Annotations::registerAnnotation('ref', array(
            'fields' => array('$small' => 'boolean', '$lazy' => 'boolean')
        ), 'property');

        Annotations::registerAnnotation('id', array(
            'fields' => array('_arg' => 'string')
        ), 'property');

        // @indexed .property
        Annotations::registerAnnotation('indexed', array(
            'fields' => array('background' => 'boolean', 'sort' => 'integer')
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

    public function loadModel($className){

        if ( $className === Document::type ) return;

        /** @var $annotation Annotations */
        $class     = new \ReflectionClass($className);
        $classInfo = Annotations::getClassAnnotation($class);
        $propertiesInfo = array();

        foreach($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property){
            if ( $property->isStatic() ) continue;

            $propertiesInfo[$property->getName()] = Annotations::getPropertyAnnotation($property);
        }

        $this->registerModel($class, $classInfo, $propertiesInfo);
    }

    /**
     * @param Annotations $classInfo
     * @param Annotations[] $propertiesInfo
     */
    private function registerModel(\ReflectionClass $class, Annotations $classInfo, $propertiesInfo){

        $name = $class->getName();
        $anCollection = $classInfo->get('collection');
        $anIndexed    = $classInfo->getAsArray('indexed');

        $info = array();
        $info['collection'] = $anCollection->getDefault( $name );

        $info['fields']     = array();
        $info['id_field']   = array('column' => '_id', 'field' => null, 'type' => 'ObjectId');
        $info['fields_rev'] = array();

        /** read properties */
        $idDefined = false;
        foreach($propertiesInfo as $nm => $property){

            if (String::startsWith($nm, '__')) continue;

            $cur = array();

            $cur['name']   = $nm;
            $cur['column'] = $property->get('column')->getDefault( $nm );
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
            if ( $class->hasMethod('set' . $nm) )
                $cur['setter'] = true;

            // getter
            if ( $class->hasMethod('get' . $nm) )
                $cur['getter'] = true;

            // indexed
            if ( $property->has('indexed') ){
                $indexed = $property->get('indexed');
                $index = array(
                    'options' => array(),
                    'fields' => array($cur['column'] => $indexed->getInteger('sort', 0))
                );
                if ( $indexed->has('$background') )
                    $index['options']['background'] = true;

                if ( $indexed->has('$unique') )
                    $index['options']['unique'] = true;

                if ( $indexed->has('$dropDups') )
                    $index['options']['dropDups'] = true;

                if ( $indexed->has('$sparse') )
                    $index['options']['sparse'] = true;

                if ( $indexed->has('$expire') )
                    $index['options']['expireAfterSeconds'] = $indexed->getInteger('$expire', 0);

                if ( $indexed->has('$w') )
                    $index['options']['w'] = $indexed->get('$w', 1);

                $info['indexed'][] = $index;
            }

            if ( $property->has('id') ){

                if ( $idDefined ){
                    throw new AnnotationException($property, 'id', 'Can\'t redeclare id field');
                }

                $info['id_field'] = array('column' => $cur['column'], 'field' => $cur['name'], 'type' => $cur['type']);
                $cur['column'] = '_id';
                $idDefined = true;
            }

            $info['fields_rev'][$cur['column']] = $nm;
            $info['fields'][$nm] = $cur;
        }

        /** all indexes */
        foreach($anIndexed as $indexed){
            $cur = array('options'=>array(), 'fields'=>array());
            foreach($indexed->getKeys() as $key){
                if (in_array($key, array('$background', '$unique', '$dropDups', '$sparse'), true))
                    $cur['options'][substr($key,1)] = true;
                elseif ( $key === '$expire' ){
                    $cur['options']['expireAfterSeconds'] = $indexed->getInteger($key, 0);
                } elseif ( $key === '$w' ){
                    $val = $indexed->get($key);
                    if ( is_numeric($val) ) $val = (int)$val;
                    $cur['options']['w'] = $val;
                } else {
                    $column = $info['fields'][ $key ];

                    if ( !$column ){

                        throw new AnnotationException($classInfo, 'indexed',
                            String::format('Can\'t find `%s` field for index', $key));
                    }
                    $cur['fields'][$column['column']] = $indexed->get($key, 0);
                }
            }
            $info['indexed'][] = $cur;
        }

        self::$modelInfo[ $name ] = $info;
    }

    /**
     * @param string $modelClass
     * @return array
     */
    public static function getModelInfo($modelClass){

        return self::$modelInfo[ $modelClass ];
    }
}


abstract class AtomicOperation {

    public $oper;
    public $value;

    public $needTyped = false;

    public function __construct($oper, $value = ''){
        $this->oper  = $oper;
        $this->value = $value;
    }

    public function getDefaultValue(){
        return null;
    }

    public function validateType($type){
        return true;
    }

    public function doTyped($type, $ref = null){
        // ...
    }
}

class AtomicInc extends AtomicOperation {

    public function __construct($value){
        parent::__construct('$inc', (int)$value);
    }

    public function getDefaultValue(){
        return $this->value;
    }

    public function validateType($type){
        return $type === 'int' || $type === 'integer' || $type === 'long';
    }
}

class AtomicRename extends AtomicOperation {

    public function __construct($value){
        parent::__construct('$rename', (string)$value);
    }
}

class AtomicPush extends AtomicOperation {

    public $needTyped = true;

    /**
     * @param $value array|mixed
     */
    public function __construct($value){

        if ( is_array($value) )
            parent::__construct('$pushAll', $value);
        else
            parent::__construct('$push', $value);
    }

    public function doTyped($type, $ref = null){

        $realType = 'mixed';
        if ( String::endsWith($type, '[]') ){
            $realType = substr($type, 0, -2);
        }

        if ( is_array($this->value) ){
            foreach($this->value as &$val){
                $val = Service::typed($val, $realType, $ref);
            }
            unset($val);
        } else {
            $this->value = Service::typed($this->value, $realType, $ref);
        }
    }
}