<?php
namespace {

    use framework\AbstractBootstrap;
    use framework\cache\Cache;
    use framework\mvc\Request;
    use framework\mvc\route\Router;
    use framework\mvc\template\BaseTemplate;

    class Bootstrap extends AbstractBootstrap {

        public function onStart(){
        }

        public function onEnvironment(&$env){
            $request = Request::current();
            if ($request->isBase('http://regenix.ru'))
                $env = 'prod';
        }

        public function onTemplateRender(BaseTemplate $template){
            $links['Main']  = '/';

            $links['Download']    = Router::path('Application.download');
            $links['Get Started'] = Router::path('Application.getStarted');
            $links['About']       = Router::path('Application.about');

            $template->put('links', $links);
        }
    }
}