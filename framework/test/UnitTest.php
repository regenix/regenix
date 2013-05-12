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

    private function assertWrite($result){
        $trace = debug_backtrace();
        $trace = $trace[1];
        $this->results[$this->currentMethod->getName()][] = array(
            'meta'   => $this->currentMethod,
            'method' => $trace['function'],
            'line' => $trace['line'],
            'file' => $trace['file'],
            'result' => (boolean)$result
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
    protected function exception($class, $callback, array $args = array()){
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
                $this->assertWrite(true);
                return;
            }
            throw $e;
        }
        $this->assertWrite(false);
    }

    protected function notException($class, $callback, array $args = array()){
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
                $this->assertWrite(false);
                return;
            }
            throw $e;
        }
        $this->assertWrite(true);
    }

    protected function eq($with, $what){
        $this->assertWrite($what == $with);
    }

    protected function eqStrong($with, $what){
        $this->assertWrite($what === $with);
    }

    protected function max($max, $what){
        $this->assertWrite($what <= $max);
    }

    protected function min($min, $what){
        $this->assertWrite($what >= $min);
    }

    protected function isTrue($what){
        $this->assertWrite($what === true);
    }

    protected function isFalse($what){
        $this->assertWrite($what === false);
    }

    protected function isNull($what){
        $this->assertWrite($what === null);
    }

    protected function notNull($what){
        $this->assertWrite($what);
    }

    protected function req($what){
        $this->assertWrite(!!($what));
    }

    protected function notReq($what){
        $this->assertWrite(!($what));
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