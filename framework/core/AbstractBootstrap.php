<?php
namespace regenix\core;

use regenix\mvc\http\Response;
use regenix\mvc\template\BaseTemplate;

abstract class AbstractBootstrap {

    /** @var Application */
    protected $app;

    public function setApp(Application $app){
        $this->app = $app;
    }

    public function onStart(){}
    public function onEnvironment(&$env){}

    public function onException(\Exception $e){ return null; }
    public function onTest(array &$tests){}
    public function onUseTemplates(){}
    public function onTemplateRender(BaseTemplate $template){}
}
