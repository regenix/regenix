<?php
namespace controllers;

use framework\mvc\Controller;
use framework\mvc\template\BaseTemplate;

class Application extends Controller {

    protected function onBefore() {
        // you can dynamic change template engine 
        // $this->setTemplateEngine(BaseTemplate::SMARTY);
    }

    public function index(){
        
        $this->renderText('OK');
        
        $this->put('var', 'Dmitriy');
        $this->render();
    }
}