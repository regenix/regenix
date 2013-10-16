<?php
namespace {

    use regenix\core\AbstractBootstrap;
    use regenix\mvc\template\TemplateLoader;

    class Bootstrap extends AbstractBootstrap {

        public function onUseTemplates(){
            TemplateLoader::registerPath(ROOT . 'documentation/');
        }
    }
}