<?php
namespace controllers;

use framework\Project;
use framework\lang\FrameworkClassLoader;
use framework\libs\I18n;
use framework\logger\Logger;
use framework\mvc\Controller;
use framework\mvc\RequestBody;

class Application extends Controller {

    public function onBefore(){
        if (!I18n::availLang())
            $this->notFound();
    }

    public function index(){
        $project = Project::current();
        var_dump( $project->router->reverse('Application.index', ['_lang' => 'ru']) );
    }

    public function post(RequestBody $body){
        $data = $body->asJSON();
    }
}