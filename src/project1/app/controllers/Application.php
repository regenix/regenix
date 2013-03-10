<?php
namespace controllers;

use framework\mvc\Controller;
use framework\mvc\template\BaseTemplate;

class Application extends Controller {

    public function onBefore() {
        // you can dynamic change template engine 
        // $this->setTemplateEngine(BaseTemplate::SMARTY);
    }

    public function index(){
        
        $this->put('var', 'Dmitriy');
        $this->render();
    }
}