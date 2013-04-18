<?php
namespace controllers;

use framework\Project;
use framework\lang\FrameworkClassLoader;
use framework\libs\Captcha;
use framework\libs\I18n;
use framework\logger\Logger;
use framework\mvc\Controller;
use framework\mvc\RequestQuery;
use framework\libs\ImageUtils;

class Application extends Controller {

    public function onBefore(){
        if (!I18n::availLang())
            $this->notFound();
    }

    public function index(){

        if ($this->request->isMethod('POST')){
            $form = $this->body->asQuery();
            if (Captcha::isValid($form->get('captcha'))){
                $this->flash->success('Captcha is Valid.');
            } else {
                $this->flash->error('Captcha is Invalid!!!');
            }
            $this->refresh();
        }

        $this->render();
    }

    public function crop($w, $h){
        $this->renderFile(ImageUtils::crop(ROOT . 'logo.png', $w, $h), false);
    }
}