<?php
namespace tests;

use framework\exceptions\CoreException;
use framework\lang\ArrayTyped;
use framework\lang\ClassLoader;
use framework\lang\String;

class LangTest extends RegenixTest {

    const type = __CLASS__;

    public function __construct(){
        $this->requiredOk(ClassloaderTest::type);
    }

    public function arrayTyped(){
        $array = array(
            'my' => 123
        );
        $typed = new ArrayTyped($array);

        $this->assert($typed->has('my'));
        $this->assertStrongEqual(123, $typed->get('my'));
        $this->assertStrongEqual('123', $typed->getString('my'));
        $this->assertStrongEqual(true, $typed->getBoolean('my'));
        $this->assertEqual('xyz', $typed->get('???', 'xyz'));
    }

    public function strings(){
        $this->assertEqual('1 abc 3', String::format('%s abc %s', 1, 3));
        $this->assertEqual('1 abc 3', String::formatArgs('%s abc %s', array(1, 3)));

        $this->assertStrongEqual('framework', String::substring('regenix framework', 8));
        $this->assertStrongEqual('regenix', $result = String::substring('framework regenix v1.0', 10, 17));

        $this->assert(String::endsWith('regenix', 'nix'));
        $this->assert(String::startsWith('regenix', 'reg'));

        $this->assert(strlen(String::random(5)) === 5);
        $this->assert(7 - strlen(String::randomRandom(5, 7)) <= 2);
    }
}