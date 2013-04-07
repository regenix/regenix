<?php
namespace controllers;

use framework\lang\FrameworkClassLoader;
use framework\logger\Logger;
use framework\mvc\Controller;


class Application extends Controller {

    public function index(){
        Logger::error('get id param = %s', $this->query->getNumber("id"));
        $this->render();
    }
}