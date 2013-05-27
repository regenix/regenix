<?php
namespace controllers;

use regenix\test\Tester;

class Application extends Tester {

    protected function onBefore(){
        $this->put('subTitle', 'Framework Testing');
    }
}