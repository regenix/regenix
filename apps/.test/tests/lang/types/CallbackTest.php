<?php
namespace tests\lang\types;

use regenix\lang\types\Callback;
use tests\RegenixTest;

class CallbackTest extends RegenixTest {

    const type = __CLASS__;

    public function testInvalid(){
        $this->assertException('InvalidArgumentException', function(){
            new Callback('FOOBAR123');
        });

        $self = $this;
        $this->assertException('InvalidArgumentException', function() use ($self) {
            new Callback($self, 'foobar');
        });

        $this->assertException('InvalidArgumentException', function() use ($self) {
            new Callback(get_class($self), 'foobar');
        });
    }

    public function testClosure(){
        $callback = new Callback(function(){
            return 'OK';
        });
        $this->assert($callback->isClosure());
        $this->assertEqual('OK', $callback->invoke());
        $this->assertNot($callback->isNop());

        $callback = new Callback(function($arg1, $arg2){
            return $arg1 . ':' . $arg2;
        });
        $this->assertEqual('foo:bar', $callback->invoke('foo', 'bar'));
        $this->assertEqual('foo:bar', $callback->invokeArgs(array('foo', 'bar')));
    }

    public function testFunction(){
        $this->assertNotException('InvalidArgumentException', function(){
            new Callback('trim');
        });

        if ($this->isLastOk()){
            $callback = new Callback('trim');
            $this->assert($callback->isFunction());
            $this->assertEqual('foobar', $callback->invoke('  foobar  '));
        }
    }

    public function testStaticCall(){
        $this->assertNotException('InvalidArgumentException', function(){
            new Callback(CallbackHandlers::type, 'foobar');
        });

        if ($this->isLastOk()){
            $callback = new Callback(CallbackHandlers::type, 'foobar');
            $this->assert($callback->isStaticMethod());
            $this->assertEqual('foobar', $callback->invoke('foobar'));
        }
    }

    public function testDynamicCall(){
        $obj = new CallbackHandlers();
        $this->assertNotException('InvalidArgumentException', function() use ($obj) {
            new Callback($obj, 'foobar2');
        });

        if ($this->isLastOk()){
            $callback = new Callback($obj, 'foobar2');
            $this->assert($callback->isDynamicMethod());
            $this->assertEqual('foobar', $callback->invoke('foobar'));
        }
    }

    public function testNop(){
        $callback = new Callback(null);
        $this->assertType(Callback::type, $callback);
        if ($this->isLastOk()){
            $this->assert($callback->isNop());
            $this->assertNull($callback->invoke());
        }

        $callback = Callback::nop();
        $this->assertType(Callback::type, $callback);
        if ($this->isLastOk()){
            $this->assert($callback->isNop());
            $this->assertNull($callback->invoke());
        }
    }
}