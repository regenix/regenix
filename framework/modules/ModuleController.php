<?php

namespace framework\modules;

use framework\lang\CoreException;
use framework\lang\String;
use framework\mvc\Controller;
use framework\mvc\template\TemplateLoader;

abstract class ModuleController extends Controller {

    const type = __CLASS__;

    /** @var AbstractModule */
    protected $module;


    public function __construct() {
        parent::__construct();
        
        $class  = explode('\\', get_class($this), 3);
        $this->module = AbstractModule::$modules[$class[1]];
        if (!$this->module)
            throw CoreException::formated('Can`t find module for %s ModuleController', $class);
        
        TemplateLoader::setAssetPath('/modules/' . $this->module->uid . '~' . $this->module->version . '/assets/');
        TemplateLoader::setControllerNamespace('.modules.' . $this->module->uid . '.controllers.');
        TemplateLoader::registerPath(ROOT . 'modules/' . $this->module->uid . '~' . $this->module->version . '/views/');
    }

    public function template($template = false){
        if (!$template){
            return str_replace(
                'modules/' . $this->module->uid . '/controllers/',
                '',
                parent::template($template));
        } else
            return parent::template($template);
    }
}
