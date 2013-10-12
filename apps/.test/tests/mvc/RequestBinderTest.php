<?php
namespace tests\mvc;

use regenix\lang\DI;
use regenix\mvc\binding\Binder;
use regenix\mvc\binding\BindValue;
use regenix\mvc\binding\exceptions\BindValueException;
use regenix\mvc\binding\exceptions\BindValueInstanceException;
use tests\RegenixTest;

class RequestBinderTest extends RegenixTest {

    /** @var Binder */
    public $binder;

    protected function onGlobalBefore(){
        $this->binder = DI::getInstance(Binder::type);
    }

    public function testBindInteger(){
        $this->assertStrongEqual(1, $this->binder->getValue('1', 'integer'));
        $this->assertStrongEqual(1, $this->binder->getValue('1', 'int'));
        $this->assertStrongEqual(1, $this->binder->getValue('1', 'long'));
    }

    public function testBindDouble(){
        $this->assertStrongEqual(1.0, $this->binder->getValue('1.0', 'double'));
        $this->assertStrongEqual(1.0, $this->binder->getValue('1.0', 'float'));
    }

    public function testBindBoolean(){
        $this->assertStrongEqual(true, $this->binder->getValue('1', 'boolean'));
        $this->assertStrongEqual(true, $this->binder->getValue('1', 'bool'));
        $this->assertStrongEqual(false, $this->binder->getValue('', 'bool'));
    }

    public function testBindString(){
        $this->assertStrongEqual('123', $this->binder->getValue(123, 'string'));
        $this->assertStrongEqual('123', $this->binder->getValue(123, 'str'));
    }

    public function testBindArray(){
        $this->assertStrongEqual(array(123), $this->binder->getValue(123, 'array'));
    }

    public function testBindObject(){
        /** @var $obj TestBindValue */
        $obj = $this->binder->getValue(123, TestBindValue::type);
        $this->assertStrongEqual(123, $obj->value);

        $self = $this;
        $this->assertNotException(BindValueException::type, function() use ($self){
            $self->binder->getValue(123, str_replace('\\', '.', TestBindValue::type));
        });

        $this->assertException(BindValueInstanceException::type, function() use ($self){
            $self->binder->getValue(123, TestBindValue2::type);
        }, array(), 'Bind non-binder implements class');

        $this->assertException(BindValueException::type, function() use ($self){
            $self->binder->getValue(123, '_Unknown_class_');
        }, array(), 'Bind non-exists class');
    }
}

class TestBindValue implements BindValue {

    const type = __CLASS__;

    public $value;

    /**
     * @param $value string
     * @param null $name
     * @return null
     */
    public function onBindValue($value, $name = null){
        $this->value = $value;
    }
}

class TestBindValue2 {
    const type = __CLASS__;
}