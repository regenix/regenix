<?php
namespace controllers;

use framework\mvc\Controller;
use framework\mvc\Annotations;
use types\Buffer;
use types\Integer;

class Application extends Controller {

    public function index(Buffer $name, Integer $id = null){
        $this->renderDump($id);
    }
}