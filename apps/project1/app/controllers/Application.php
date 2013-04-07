<?php
namespace controllers;

use framework\lang\FrameworkClassLoader;
use framework\logger\Logger;
use framework\mvc\Controller;


class Application extends Controller {

    public function index($id){

        $this->render('ok.html');
    }
}