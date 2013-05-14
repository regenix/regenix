<?php
namespace tests\mvc;

use tests\BaseTest;

class FlashTest extends BaseTest {

    public function __construct(){
        $this->requiredOk(SessionTest::type);
    }


}