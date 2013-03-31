<?php
namespace controllers;

use framework\mvc\Controller;
use framework\mvc\Annotations;
use types\Buffer;
use types\Integer;
use types\MyForm;

class Application extends Controller {

    public function index(MyForm $form){
        $this->renderDump($form);
    }
}