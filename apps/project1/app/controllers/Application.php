<?php
namespace controllers;

use framework\mvc\Controller;
use types\MyForm;

class Application extends Controller {

    public function index(){

        $this->put('var', '<b>my</b>');
        $this->put('list', array('a', 'b', 'c', 'd'));
        $this->render();
    }
}