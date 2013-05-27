<?php
namespace {

    use regenix\AbstractBootstrap as BaseBootstrap, regenix\Project;
    use regenix\cache\Cache;
    use regenix\libs\I18n;
    use regenix\mvc\Request;
    use regenix\mvc\route\Router;
    use regenix\mvc\template\BaseTemplate;

    class Bootstrap extends BaseBootstrap {

        public function onStart(){
            die('die.');
            $request = Request::current();
            if ($request->isBase('http://regenix.ru') || $request->isBase('http://localhost'))
                I18n::setLang('ru');
            else
                I18n::setLang('en');
        }

        public function onEnvironment(&$env){
            $request = Request::current();
            if ($request->isBase('http://regenix.ru'))
                $env = 'prod';
        }

        public function onTemplateRender(BaseTemplate $template){
            $links['Home']  = '/';

            $links['About']       = Router::path('Application.page', array('page' => 'about'));
            $links['Download']    = Router::path('Application.page', array('page' => 'download'));
            $links['Get Started'] = Router::path('Application.page', array('page' => 'getstarted'));
            $links['Documentation'] = Router::path('Application.page', array('page' => 'documentation'));
            $links['Community']   = Router::path('Application.page', array('page' => 'community'));

            $template->put('links', $links);
        }
    }
}