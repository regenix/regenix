<?php
namespace controllers;

use framework\mvc\Controller;

class Application extends Controller {

 
    public function index(){
        
        //$data = SystemCache::getFileContents('src/project1/conf/route');
        //$data = file_get_contents('src/project1/conf/route');
        
        $this->put('var', 'Dmitriy');
        $this->render();
    }
}