<?php
namespace tests\lang;

use regenix\lang\DI;
use regenix\lang\String;
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