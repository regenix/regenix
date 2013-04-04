<?php
namespace controllers;

use framework\mvc\Controller;
use types\MyForm;

class Application extends Controller {

    public function index(){
        $this->flash->success("Прошло успешно");
        $this->redirect("/test/");
    }

    public function test(){
        $this->renderTemplate("Application/index.html");
    }
}