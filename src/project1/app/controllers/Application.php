<?php
namespace controllers;

use framework\mvc\Controller;
use framework\mvc\template\BaseTemplate;

class Application extends Controller {

   
    public function index(){
        
        $this->renderText( 'OK' );
        
        $this->render();
    }
}