<?php
namespace controllers;

use framework\lang\FrameworkClassLoader;
use framework\mvc\Controller;


class Application extends Controller {

    public function index(){
        $this->render();
    }
}