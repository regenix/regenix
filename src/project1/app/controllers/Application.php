<?php
namespace controllers;

use framework\mvc\Controller;


class Application extends Controller {

    public function onBefore() {
       
        $this->put("user", "test");
    }

    public function index(){
        
        $this->render();
    }
}