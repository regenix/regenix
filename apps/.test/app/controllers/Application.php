<?php
namespace controllers;

use framework\test\Tester;

class Application extends Tester {

    protected function onBefore(){
        $this->put('subTitle', 'Framework Testing');
    }
}