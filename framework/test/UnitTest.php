<?php
namespace framework\test;

/**
 * Class UnitTest
 * @package framework\test
 */
abstract class UnitTest {

    public static $tested = array();

    /** @var array */
    protected $requires = array();

    /** @var \ReflectionMethod */
    protected $currentMethod = null;
    private $results = array();

    protected function onBefore(){}
    protected function onAfter(){}

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

    protected function requiredOk($unitTestClass){
        $this->required($unitTestClass, true);
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

    public function startTesting(){
        self::$tested[get_class($this)] = false;

        foreach($this->requires as $require => $options){
            if ( !isset(self::$tested[$require]) ){
                /** @var $test UnitTest */
                $test = new $require();
                $test->startTesting();
                if ( $options['needOk'] && !$test->isOk() )
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
            $method->invoke($this);
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