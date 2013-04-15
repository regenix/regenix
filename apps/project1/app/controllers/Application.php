<?php
namespace controllers;

use framework\Project;
use framework\io\File;
use framework\lang\FrameworkClassLoader;
use framework\libs\I18n;
use framework\logger\Logger;
use framework\mvc\Controller;
use framework\mvc\RequestBody;
use framework\mvc\RequestQuery;
use framework\libs\ImageUtils;

class Application extends Controller {

    public function onBefore(){
        if (!I18n::availLang())
            $this->notFound();
    }

    public function index(){
        $this->render();
    }

    public function crop($w, $h){
        $this->renderFile(ImageUtils::crop(ROOT . 'logo.png', $w, $h), false);
    }
}