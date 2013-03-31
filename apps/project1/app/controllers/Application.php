<?php
namespace controllers;

use framework\mvc\Controller;
use types\MyForm;

class Application extends Controller {

    public function index(){

        $this->put('var', 'URA!!!');
        $this->render();
    }
}