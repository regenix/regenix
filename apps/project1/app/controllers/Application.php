<?php
namespace controllers;

use framework\mvc\Controller;
use tests\IntegerTest;
use types\MyForm;

class Application extends Controller {

    public function index(){
        $this->flash->success("Прошло успешно");
        $this->redirect("/test/", true);
    }

    public function test(){

        $test = new IntegerTest();
        $test->startTesting();

        $this->renderDump($test->getResult());
    }
}