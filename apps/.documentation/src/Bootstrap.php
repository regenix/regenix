<?php
namespace {

    use regenix\core\AbstractBootstrap;
    use regenix\mvc\http\Request;
    use regenix\mvc\template\TemplateLoader;

    class Bootstrap extends AbstractBootstrap {

        public function onEnvironment(&$env) {
            $request = Request::getInstance();
            if ($request->isBase('http://regenix.ru'))
                $env = 'prod';
        }

        public function onUseTemplates(){
            TemplateLoader::registerPath(ROOT . 'documentation/');
        }
    }
}