<?php
namespace controllers;

use framework\Project;
use framework\lang\FrameworkClassLoader;
use framework\libs\I18n;
use framework\logger\Logger;
use framework\mvc\Controller;
use framework\mvc\RequestBody;
use framework\mvc\RequestQuery;
use models\Post;

class Application extends Controller {

    public function onBefore(){
        if (!I18n::availLang())
            $this->notFound();
    }

    public function index(){
        $service = Post::getService();
        $this->renderVar($this->body);
    }

    public function post(RequestBody $body){
        $data = $body->asJSON();
    }
}