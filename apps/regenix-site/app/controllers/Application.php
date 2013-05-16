<?php
namespace controllers;

use framework\cache\Cache;
use notifiers\ConfirmNotifier;

class Application extends AppController {

    public function index(){
        $this->render();
    }

    public function about(){
        $this->render();
    }

    public function getStarted(){
        $confirms = new ConfirmNotifier();
        var_dump($confirms->welcome('dz@dim-s.net'));

        $this->render();
    }

    public function download(){
        $this->render();
    }
}