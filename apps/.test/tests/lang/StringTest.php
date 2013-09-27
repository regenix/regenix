<?php
namespace tests\lang;

use regenix\lang\String;
use tests\RegenixTest;

class StringTest extends RegenixTest {

    public function testSimple(){
        $this->assert(String::startsWith('foobar', 'foo'));
        $this->assert(String::endsWith('foobar', 'bar'));

        $this->assertEqual('bar', String::substring('foobar', 3));
        $this->assertEqual('ar', String::substring('foobar', -2));
        $this->assertEqual('ba', String::substring('foobar', 3, 5));
    }

    public function testFormat(){
        $this->assertEqual('foobar', String::format('foo%s', 'bar'));
        $this->assertEqual('foobar', String::format('%s%s', 'foo', 'bar'));
        $this->assertEqual('foobar', String::formatArgs('foo%s', array('bar')));
        $this->assertEqual('foobar', String::formatArgs('%s%s', array('foo', 'bar')));
    }

    public function testRandom(){
        $one = String::random(45, true, false);
        $this->assertStringLength(45, $one);
        $this->assertPattern('#^([a-z0-9]+)$#i', $one);

        $one = String::random(45, false, false);
        $this->assertStringLength(45, $one);
        $this->assertPattern('#^([a-z]+)$#i', $one);

        $one = String::random(100, true, true);
        $this->assertStringLength(100, $one);
        $this->assertNotPattern('#^([a-z0-9]+)$#i', $one);

        for($i = 0; $i < 5; $i++){
            $one = String::randomRandom(40, 42);
            $this->assert( strlen($one) >= 40 && strlen($one) <= 42 );
        }
    }
}