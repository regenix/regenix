<?php
namespace controllers;


use framework\cache\Cache;

class Application extends AppController {

    public function index(){
        $this->render();
    }
}