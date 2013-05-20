<?php
namespace tests\mvc;

use framework\mvc\BindValueException;
use framework\mvc\BindValueInstanceException;
use framework\mvc\RequestBindValue;
use framework\mvc\RequestBinder;
use tests\RegenixTest;

class RequestBinderTest extends RegenixTest {

    public function testBindInteger(){
        $this->assertStrongEqual(1, RequestBinder::getValue('1', 'integer'));
        $this->assertStrongEqual(1, RequestBinder::getValue('1', 'int'));
        $this->assertStrongEqual(1, RequestBinder::getValue('1', 'long'));
    }

    public function testBindDouble(){
        $this->assertStrongEqual(1.0, RequestBinder::getValue('1.0', 'double'));
        $this->assertStrongEqual(1.0, RequestBinder::getValue('1.0', 'float'));
    }

    public function testBindBoolean(){
        $this->assertStrongEqual(true, RequestBinder::getValue('1', 'boolean'));
        $this->assertStrongEqual(true, RequestBinder::getValue('1', 'bool'));
        $this->assertStrongEqual(false, RequestBinder::getValue('', 'bool'));
    }

    public function testBindString(){
        $this->assertStrongEqual('123', RequestBinder::getValue(123, 'string'));
        $this->assertStrongEqual('123', RequestBinder::getValue(123, 'str'));
    }

    public function testBindArray(){
        $this->assertStrongEqual(array(123), RequestBinder::getValue(123, 'array'));
    }

    public function testBindObject(){
        /** @var $obj TestBindValue */
        $obj = RequestBinder::getValue(123, TestBindValue::type);
        $this->assertStrongEqual(123, $obj->value);

        $this->assertNotException(BindValueException::type, function(){
            RequestBinder::getValue(123, str_replace('\\', '.', TestBindValue::type)); });

        $this->assertException(BindValueInstanceException::type, function(){
            RequestBinder::getValue(123, TestBindValue2::type); }, array(), 'Bind non-binder implements class');

        $this->assertException(BindValueException::type, function(){
            RequestBinder::getValue(123, '_Unknown_class_'); }, array(), 'Bind non-exists class');
    }
}

class TestBindValue implements RequestBindValue {

    const type = __CLASS__;

    public $value;

    /**
     * @param $value string
     * @return null
     */
    public function onBindValue($value){
        $this->value = $value;
    }
}

class TestBindValue2 {
    const type = __CLASS__;
}