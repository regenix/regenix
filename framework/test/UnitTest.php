<?php
namespace framework\test;

use framework\exceptions\StrictObject;
use framework\exceptions\TypeException;
use framework\lang\ClassLoader;

/**
 * Class UnitTest
 * @package framework\test
 */
abstract class UnitTest extends StrictObject {

    /** @var UnitTest[] */
    public static $tested = array();

    /** @var array */
    protected $requires = array();

    /** @var \ReflectionMethod */
    protected $currentMethod = null;
    private $results = array();

    protected function onBefore(){}
    protected function onAfter(){}
    protected function onException(\Exception $e){}

    protected function onGlobalBefore(){}
    protected function onGlobalAfter(){}

    private function assertWrite($result, $message = ''){
        $trace = debug_backtrace();
        $trace = $trace[1];
        $this->results[$this->currentMethod->getName()][] = array(
            'meta'   => $this->currentMethod,
            'method' => $trace['function'],
            'line' => $trace['line'],
            'file' => $trace['file'],
            'result' => (boolean)$result,
            'message' => $message
        );
    }

    protected function required($unitTestClass, $needOk = false){
        $this->requires[$unitTestClass] = array('needOk' => $needOk);
    }

    public function getRequires(){
        return $this->requires;
    }

    protected function requiredOk($unitTestClass){
        $this->required($unitTestClass, true);
    }

    /**
     * @param string $class
     * @param callable $callback
     * @param array $args
     * @throws \framework\exceptions\TypeException
     */
    protected function exception($class, $callback, array $args = array(), $message = ''){
        if (!is_callable($callback))
            throw new TypeException('$callback', 'callable');

        ClassLoader::load($class);
        $meta = new \ReflectionClass($class);
        if (!$meta->isSubclassOf("\\Exception") && $meta->getName() !== '\\Exception')
            throw new TypeException('$class', 'Exception class');

        try {
            call_user_func_array($callback, $args);
        } catch (\Exception $e){
            if ($meta->isSubclassOf($class) || $meta->getName() === $class){
                $this->assertWrite(true, $message);
                return;
            }
            throw $e;
        }
        $this->assertWrite(false, $message);
    }

    protected function notException($class, $callback, array $args = array(), $message = ''){
        if (!is_callable($callback))
            throw new TypeException('$callback', 'callable');

        ClassLoader::load($class);
        $meta = new \ReflectionClass($class);
        if (!$meta->isSubclassOf("\\Exception") && $meta->getName() !== '\\Exception')
            throw new TypeException('$class', 'Exception class');

        try {
            call_user_func_array($callback, $args);
        } catch (\Exception $e){
            if ($meta->isSubclassOf($class) || $meta->getName() === $class){
                $this->assertWrite(false, $message);
                return;
            }
            throw $e;
        }
        $this->assertWrite(true, $message);
    }

    protected function eq($with, $what, $message = ''){
        $this->assertWrite($what == $with, $message);
    }

    protected function notEq($with, $what, $message = ''){
        $this->assertWrite($what != $with, $message);
    }

    protected function eqStrong($with, $what, $message = ''){
        $this->assertWrite($what === $with, $message);
    }

    protected function max($max, $what, $message = ''){
        $this->assertWrite($what <= $max, $message);
    }

    protected function min($min, $what, $message = ''){
        $this->assertWrite($what >= $min, $message);
    }

    protected function isTrue($what, $message = ''){
        $this->assertWrite($what === true, $message);
    }

    protected function isFalse($what, $message = ''){
        $this->assertWrite($what === false, $message);
    }

    protected function isNull($what, $message = ''){
        $this->assertWrite($what === null, $message);
    }

    protected function isType($what, $object, $message = ''){
        switch($what){
            case 'array': $this->assertWrite(is_array($object), $message); break;
            case 'string': $this->assertWrite(is_string($object), $message); break;
            case 'int':
            case 'integer': $this->assertWrite(is_int($object), $message); break;
            case 'double':
            case 'float': $this->assertWrite(is_double($object), $message); break;
            case 'callable': $this->assertWrite(is_callable($object), $message); break;
            case 'object': $this->assertWrite(is_object($object), $message); break;
            default: {
                $this->assertWrite(is_a($object, $what), $message);
            }
        }
    }

    protected function arraySize($what, $array, $message = ''){
        $this->isType('array', $array);
        $this->assertWrite(is_array($array) && (sizeof($array) === (int)$what), $message);
    }

    protected function isNotNull($what, $message = ''){
        $this->assertWrite($what, $message);
    }

    protected function req($what, $message = ''){
        $this->assertWrite(!!($what), $message);
    }

    protected function issetArray($what, array $keys, $message = ''){
        if (!is_array($what))
            $this->assertWrite(false, $message);

        foreach($keys as $key){
            if (!isset($what[$key])){
                $this->assertWrite(false, $message);
                return;
            }
        }
        $this->assertWrite(true, $message);
    }

    protected function notReq($what, $message = ''){
        $this->assertWrite(!($what), $message);
    }

    public function startTesting(){
        self::$tested[get_class($this)] = false;

        foreach($this->requires as $require => $options){
            if ( !isset(self::$tested[$require]) ){
                /** @var $test UnitTest */
                $test = new $require();
                $test->startTesting();
                if ( $options['needOk'] && (!$test->isOk() || self::$tested[$require] === false) )
                    return null;
            } else {

                if ( self::$tested[$require] !== false &&
                    ($options['needOk'] && !self::$tested[$require]->isOk()) )
                    return null;
            }
        }

        $this->onGlobalAfter();
        $class   = new \ReflectionClass($this);
        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach($methods as $method){
            $declClass = $method->getDeclaringClass();
            if ( $declClass->isAbstract() ) continue;

            $this->currentMethod = $method;
            $this->onBefore();
            try {
                $method->invoke($this);
            } catch (\Exception $e){
                $this->onException($e);
            }
            $this->onAfter();
        }
        $this->onGlobalAfter();
        return self::$tested[ get_class($this) ] = $this;
    }

    public function getResult(){
        return $this->results;
    }

    public function isOk(){
        foreach($this->results as $method => $results){
            foreach($results as $result){
                if ( !$result['result'] )
                    return false;
            }
        }
        return true;
    }
}