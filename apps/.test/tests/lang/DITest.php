<?php
namespace tests\lang;

use regenix\lang\DI;
use regenix\lang\DependencyInjectionException;
use tests\RegenixTest;
use tests\lang\impl\DISingleton;

class DITest extends RegenixTest {

    public function testSimpleSingleTon(){
        DI::bind(new Singleton());

        /** @var $one Singleton */
        $one = DI::getInstance(Singleton::type);
        $two = DI::getInstance(Singleton::type);

        $this->assertType(Singleton::type, $one);
        $this->assert($one && $one->equals($two));

        DI::bind(new Singleton());
        $three = DI::getInstance(Singleton::type);
        $this->assertNot($one && $one->equals($three));
    }

    public function testSimple(){
        DI::bindTo(ISingleton::i_type, Singleton::type);
        /** @var $one Singleton */
        $one = DI::getInstance(ISingleton::i_type);

        $this->assertNotNull($one);
        $this->assertType(Singleton::type, $one);

        $two = DI::getInstance(ISingleton::i_type);
        $this->assertNot($one && $one->equals($two));
    }

    public function testStaticSingleton(){
        DI::bindTo(ISingleton::i_type, Singleton::type, true);
        /** @var $one Singleton */
        $one = DI::getInstance(ISingleton::i_type);
        $two = DI::getInstance(ISingleton::i_type);

        $this->assertType(Singleton::type, $one);
        if ($this->isLastOk()){
            $this->assert($one->equals($two));
        }

        DI::clear();

        $one = DI::getSingleton(Singleton::type);
        $two = DI::getSingleton(Singleton::type);
        $three = DI::getInstance(Singleton::type);

        $this->assertType(Singleton::type, $one);
        $this->assert($one && $one->equals($two));
        $this->assert($one && $one->equals($three));
    }

    public function testFunctionalInjection(){
        $self = $this;
        DI::clear();

        global $tmp_interfaceClass; // T_T php 5.3
        DI::bindTo(ISingleton::i_type, function($interfaceClass) use ($self) {
            global $tmp_interfaceClass;
            $tmp_interfaceClass = $interfaceClass;
            return new Singleton();
        });

        $one = DI::getInstance(ISingleton::i_type);
        $this->assertType(ISingleton::i_type, $one);
        $this->assertEqual(ISingleton::i_type, $tmp_interfaceClass);

        $two = DI::getInstance(ISingleton::i_type);
        $this->assertType(ISingleton::i_type, $two);

        $this->assertNot($one && $one->equals($two));

        $this->assertException(DependencyInjectionException::type, function(){
            DI::getSingleton(ISingleton::i_type);
        });

        DI::clear();
        global $tmp_one, $tmp_two;
        $this->assertNotException(DependencyInjectionException::type, function() use ($self){
            global $tmp_one, $tmp_two;
            $tmp_one = DI::getSingleton(Singleton::type);
            $tmp_two = DI::getSingleton(Singleton::type);
        });

        $this->assertType(Singleton::type, $tmp_one);
        $this->assert($tmp_one && $tmp_one->equals($tmp_two));

        DI::clear();
        DI::bindTo(ISingleton::i_type, Singleton::type, true);
        $this->assertNotException(DependencyInjectionException::type, function() use ($self) {
            global $tmp_one, $tmp_two;
            $tmp_one = DI::getSingleton(ISingleton::i_type);
            $tmp_two = DI::getSingleton(ISingleton::i_type);
        });

        $this->assertType(ISingleton::i_type, $tmp_one);
        $this->assert($tmp_one && $tmp_one->equals($tmp_two));

        DI::clear();
        $one = new Singleton();
        $two = DI::getSingleton(Singleton::type, $one);
        $this->assert($one->equals($two));
    }

    public function testArgs(){
        DI::clear();
        DI::bind($one = new Singleton());

        /** @var ArgsClass $instance */
        $instance = DI::getInstance(ArgsClass::type);
        $this->assert($one->equals($instance->getOne()));
    }

    public function testNamespaces(){
        DI::clear();
        DI::bindNamespaceTo('tests.lang.', 'tests.lang.impl.DI');

        $one = DI::getInstance(Singleton::type);
        $two = DI::getInstance(Singleton::type);
        $this->assertType(DISingleton::type, $one);
        $this->assertType(DISingleton::type, $two);
        $this->assertNot($one && $one->equals($two));

        DI::clear();
        DI::bindNamespaceTo('tests.lang.', 'tests.lang.impl.DI', true);
        /** @var $one Singleton */
        $one = DI::getInstance(Singleton::type);
        $two = DI::getInstance(Singleton::type);

        $this->assertType(DISingleton::type, $one);
        $this->assertType(DISingleton::type, $two);
        $this->assert($one && $one->equals($two));
    }
}

interface ISingleton {
    const i_type = __CLASS__;
}

class Singleton implements ISingleton {

    const type = __CLASS__;

    public $id;

    public function __construct(){
        $this->id = mt_rand(0, 1000000);
    }

    public function equals($object){
        if ((!$object instanceof Singleton))
            return false;

        return $this->id === $object->id;
    }
}

class ArgsClass {

    const type = __CLASS__;

    private $one;

    public function __construct(Singleton $one){
        $this->one = $one;
    }

    /**
     * @return \tests\lang\Singleton
     */
    public function getOne()
    {
        return $this->one;
    }
}