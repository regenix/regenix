<?php
namespace tests;

use framework\mvc\route\Router;
use framework\test\UnitTest;
use types\Integer;

class IntegerTest extends UnitTest {

    const type = __CLASS__;

    public function __construct(){
        $this->requiredOk(IntegerTest::type);
    }

    protected function onGlobalBefore(){
        // TODO Log...
    }

    public function testOne(){
        $val = new Integer();
        $this->req($val);
    }
}