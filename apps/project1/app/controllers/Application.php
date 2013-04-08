<?php
namespace controllers;

use framework\lang\FrameworkClassLoader;
use framework\libs\I18n;
use framework\logger\Logger;
use framework\mvc\Controller;

class Application extends Controller {

    public function onBefore(){
        if (!I18n::availLang())
            $this->notFound();
    }

    public function index($id){
        $this->renderText( I18n::get("Admin.Panel", 123, "YES") );
    }
}