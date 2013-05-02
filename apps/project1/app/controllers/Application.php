<?php
namespace controllers;

use framework\Project;
use framework\logger\Logger;
use framework\mvc\Controller;
use framework\mvc\RequestQuery;
use framework\libs\ImageUtils;

class Application extends Controller {

    public function index(){
        $this->render();
    }
}