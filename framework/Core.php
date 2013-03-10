<?php
namespace framework {

use framework\di\DI;
use framework\mvc\results\Result;
use framework\mvc\template\TemplateLoader;

class Core {
    
    /** @var string */
    public static $tempDir = 'tmp/';
    
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
        require 'framework/cache/InternalCache.php';
        require 'framework/di/DI.php';
        
        self::_registerLogger();
        self::_registerProjects();
        self::_registerCurrentProject();
        
        TemplateLoader::register('\framework\mvc\template\PHPTemplate');
        TemplateLoader::registerPath(self::getFrameworkPath() . 'views/', false);
        
        register_shutdown_function(array('\framework\Core','shutdown'), self::$__project);
    }

    private static function _registerLogger(){
        DI::define('Logger', '\framework\logger\LoggerDefault', true);
    }

    private static function _registerProjects(){

        self::$projects['project1'] = new Project('project1');
        $dirs = scandir(Project::getSrcDir(), SCANDIR_SORT_NONE);
        foreach ($dirs as $dir){
            if ($dir == '.' || $dir == '..') continue;
            self::$projects[ $dir ] = new Project( $dir );
        }
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
        $router  = $project->router;
        
        $request = mvc\Request::current();
        $request->setBasePath( $project->getUriPath() );
                
        $router->route($request);

        try {
                
            if (!$router->action){
                throw new exceptions\CoreException('404 Not found');
            }

            // TODO optimize ?
            $tmp = explode('.', $router->action);
            $controllerClass = 'controllers\\' . implode('\\', array_slice($tmp, 0, -1));
            $actionMethod    = $tmp[ sizeof($tmp) - 1 ];
            
            $controller = new $controllerClass;
            $reflection = new \ReflectionMethod($controller, $actionMethod);
            
            if ( !$reflection->isPublic() || $reflection->isStatic() ){
                throw new exceptions\CoreException(
                        utils\StringUtils::format('Can\'t use "%s.%s()" as action method', $controllerClass, $actionMethod));   
            }
            
            $controller->onBefore();
            $reflection->invoke($controller);
            
        } catch (Result $result){
            $response = $result->getResponse();
        } catch (\Exception $e){
            
            if ( $controller ){
                try {
                    $responseErr = $controller->onException($e);
                } catch (Result $result){
                    $responseErr = $result->getResponse();
                }
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
    
    public static function shutdown(Project $project){
        
        ignore_user_abort(true);   
        
        ob_end_flush();
        ob_flush();
        flush();
        
        $cache = c('Cache');
        $cache->flush(true);
    }


    /*** utils ***/
    public static function isDev(){
        return Project::current()->isDev();
    }
    
    public static function isProd(){
        return Project::current()->isProd();
    }
    
    public static function isMode($mode){
        return Project::current()->isMode($mode);
    }
    
    public static function setTempDir($dir){
        if ( !is_dir($dir) ){
            if ( !mkdir($dir, 0777, true) ){
                throw new exceptions\CoreException('Unable to create temp directory `/tmp/`');
            }
            chmod($dir, 0777);
        }
        self::$tempDir = $dir;
    }
}

}

namespace {
    
    function dump($var){
        echo '<pre class="_dump">';
        print_r($var);
        echo '</pre>';
    }
}