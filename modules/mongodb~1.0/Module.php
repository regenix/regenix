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

    /**
     * @return array
     */
    public static function getDeps() {
        return array();
    }

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

        // @indexed .class
        Annotations::registerAnnotation('indexed', array(
            'fields' => array('$background' => 'boolean'),
            'multi' => true,
            'any' => true
        ), 'class');

        // @indexed .property
        Annotations::registerAnnotation('indexed', array(
            'fields' => array('background' => 'boolean', 'sort' => 'integer')
        ), 'property');
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