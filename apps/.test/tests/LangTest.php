<?php
namespace tests;

use framework\exceptions\CoreException;
use framework\lang\ArrayTyped;
use framework\lang\ClassLoader;
use framework\lang\String;

class LangTest extends BaseTest {

    const type = __CLASS__;

    public function __construct(){
        $this->requiredOk(ClassloaderTest::type);
    }

    public function arrayTyped(){
        $array = array(
            'my' => 123
        );
        $typed = new ArrayTyped($array);

        $this->isTrue($typed->has('my'));
        $this->eqStrong(123, $typed->get('my'));
        $this->eqStrong('123', $typed->getString('my'));
        $this->eqStrong(true, $typed->getBoolean('my'));
        $this->eq('xyz', $typed->get('???', 'xyz'));
    }

    public function strings(){
        $this->eq('1 abc 3', String::format('%s abc %s', 1, 3));
        $this->eq('1 abc 3', String::formatArgs('%s abc %s', array(1, 3)));

        $this->eqStrong('framework', String::substring('regenix framework', 8));
        $this->eqStrong('regenix', $result = String::substring('framework regenix v1.0', 10, 17));

        $this->isTrue(String::endsWith('regenix', 'nix'));
        $this->isTrue(String::startsWith('regenix', 'reg'));
    }
}