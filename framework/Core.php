<?php
namespace framework;

use framework\di\DI;
use framework\mvc\results\Result;
use framework\mvc\template\TemplateLoader;

class Core {

    /**
     *
     * @var array
     */
    private static $projects = array();
    
    /**
     * @var Project
     */
    public static $__project = null;


    static function init(){
        // TODO
        error_reporting(E_ALL ^ E_NOTICE);
        
        require 'framework/utils/ClassLoader.php';
        require 'framework/di/DI.php';
        require 'framework/global.php';
        
        self::_registerLogger();
        self::_registerProjects();
        self::_registerCurrentProject();
        self::_registerTemplate();
    }

    private static function _registerLogger(){
        DI::bind('\framework\logger\Logger')
                ->to('\framework\logger\LoggerDefault')->asSingleton();
    }

    private static function _registerProjects(){

        foreach (new \DirectoryIterator( Project::getSrcDir() ) as $info) {
            if ( $info->isDir() && !$info->isDot()){
                $projectName = $info->getFilename();
                self::$projects[ $projectName ] = new Project( $projectName );
            }
        }
    }
    
    private static function _registerTemplate(){
        
        TemplateLoader::register('\framework\mvc\template\PHPTemplate');
        TemplateLoader::registerPath(self::getFrameworkPath() . 'views/', false);
    }

    private static function _registerCurrentProject(){
        
        /** 
         * @var Project $project
         */
        foreach (self::$projects as $project){
            
            $url = $project->findCurrentPath();
            if ( $url ){
                self::$__project = $project;
                $project->setUriPath( $url );
                $project->register();
                return;
            }
        }
        
        throw new exceptions\CoreException("Can't find project for current request");
    }    
    
    public static function proccessRoute(){
        
        $project = Project::current();
        
        $request = mvc\Request::current();
        $request->setBasePath( Project::current()->getUriPath() );
        
        $route = $project->router->route($request);
        
        
        $controllerClass = '\controllers\\Application';
        $actionMethod    = 'index';
        
        $controller = new $controllerClass;
        try {
            $controller->onBefore();
            $response = $controller->{$actionMethod}();
        } catch (Result $result){
            $response = $result->getResponse();
        } catch (\Exception $e){
            try {
                $responseErr = $controller->onException($e);
            } catch (Result $result){
                $responseErr = $result->getResponse();
            }
            
            if ( !$responseErr )
                throw $e;
            else {
                $response = $responseErr;
            }
        }
        
        if ( !$responseErr )
            $controller->onAfter();
        
        if ( !$response ){
            throw new exceptions\CoreException('Unknow type of controller result for response');
        }
        
        $response->send();
        $controller->onFinaly();
    }
    
    
    public static function getFrameworkPath(){
        
        return 'framework/';
    }
    
    
    /*** utils ***/
    public static function isDev(){
        return Project::current()->isDev();
    }
    
    public static function isProd(){
        return Project::current()->isProd();
    }
}
