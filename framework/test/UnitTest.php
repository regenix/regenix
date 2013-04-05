<?php
namespace framework\test;

/**
 * Class UnitTest
 * @package framework\test
 */
abstract class UnitTest {

    protected function onBefore($method){}
    protected function onAfter($method){}

    protected function onGlobalBefore(){}
    protected function onGlobalAfter(){}

    private function assertWrite($method, $result){
        // TODO
    }

    protected function eq($with, $what){
        $this->assertWrite('eq', $what == $with);
    }

    protected function eqStrong($with, $what){
        $this->assertWrite('eq_strong', $what === $with);
    }

    protected function max($max, $what){
        $this->assertWrite('max', $what <= $max);
    }

    protected function min($min, $what){
        $this->assertWrite('min', $what >= $min);
    }

    protected function isTrue($what){
        $this->assertWrite('true', $what === true);
    }

    protected function isFalse($what){
        $this->assertWrite('false', $what === false);
    }

    protected function isNull($what){
        $this->assertWrite('null', $what === null);
    }

    protected function notNull($what){
        $this->assertWrite('not_null', $what);
    }

    protected function req($what){
        $this->assertWrite('not_empty', !!($what));
    }
}