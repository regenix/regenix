<?php
namespace tests;

use framework\test\UnitTest;
use types\Integer;

class IntegerTest extends UnitTest {

    protected function onBefore($method){
        // TODO Log...
    }

    public function testOne(){
        $val = new Integer();
        $this->isNull($val);
    }
}