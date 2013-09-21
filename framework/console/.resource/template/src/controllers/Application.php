<?php
namespace controllers;

use regenix\mvc\Controller;

class Application extends Controller {
    public function index(){
        $this->renderText("Hello World!");
    }
}