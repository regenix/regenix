<?php
namespace {

    use framework\AbstractBootstrap;
    use framework\mvc\Request;
    use framework\mvc\template\BaseTemplate;

    class Bootstrap extends AbstractBootstrap {

        public function onStart(){

        }

        public function onEnvironment(&$env){
            $request = Request::current();
            if ($request->isBase('http://regenix.ru'))
                $env = 'prod';
        }

        public function onUseTemplates(){

        }
    }
}