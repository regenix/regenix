<?php namespace modules\console\controllers;

use framework\modules\ModuleController;

class Main extends ModuleController {
    
    public function onBefore() {
        parent::onBefore();
        $this->setTemplateEngine('Twig');
    }

    public function index(){
        $this->render('Main/index');
    }
}