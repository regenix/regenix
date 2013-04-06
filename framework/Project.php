<?php

namespace framework;

use framework\io\File;

use framework\config\Configuration;
use framework\config\PropertiesConfiguration;
use framework\mvc\ModelClassloader;
use framework\mvc\route\RouterConfiguration;
use framework\config\ConfigurationReadException;

use framework\mvc\template\TemplateLoader;
use framework\di\DI;

use framework\cache\SystemCache;
use framework\lang\ClassLoader;

class Project {

    private $name;
    private $paths = array();
    
    /** @var string */
    private $currentPath;

    /** @var ClassLoader */
    public $classLoader;
    
    /** @var string */
    private $mode = 'dev';
    
    /** @var string */
    private $secret;

    /** @var PropertiesConfiguration */
    public $config;
    
    /** @var mvc\route\Router */
    public $router;

    /**
    * @param string $projectName root directory name of project
    */
    public function __construct($projectName){
        $this->name   = $projectName;
        
        $configFile   = $this->getPath() . 'conf/application.conf';
        $this->config = SystemCache::getWithCheckFile('appconf', $configFile);
        
        if ($this->config === null){
            $this->config = new PropertiesConfiguration(new File( $configFile ));
            SystemCache::setWithCheckFile('appconf', $this->config, $configFile);            
        }
        $this->applyConfig( $this->config ); 
    }

    /**
     * get project name (root directory name)
     * @return string
     */
    public function getName(){
        return $this->name;
    }

    /**
     * get project root path
     * @return string
     */
    public function getPath(){
        return self::getSrcDir() . '/' . $this->name . '/';
    }
    
    public function getViewPath(){
        return self::getPath() . 'app/views/';
    }

    public function getModelPath(){
        return self::getPath() . 'app/models/';
    }

    public function getTestPath(){
        return self::getPath() . 'app/tests/';
    }

    /*
     * пути можно указывать с доменами и с портами
     * examples:
     *
     *   domain.com:80/s1/
     *   domain.com:80/s2/
     */
    public function setPaths(array $paths){
        foreach(array_unique($paths) as $path){
            $this->paths[ $path ] = new net\URL( $path );
        }
    }


    /**
     * replace part configuration
     * @param \framework\config\Configuration|\framework\config\PropertiesConfiguration $config
     */
    public function applyConfig(PropertiesConfiguration $config){
        $paths = $config->getArray("app.paths", array('/'));
        $this->setPaths( $paths );
    }
    
    
    /**
     * @return boolean
     */
    public function findCurrentPath(){
        $request = mvc\Request::current();
        
        foreach ($this->paths as $url){
            if ( $request->isBase( $url ) )
                return $url;
        }
        
        return null;
    }


    public function setUriPath(net\URL $url){
        $this->currentPath = $url->getPath();
    }

    public function getUriPath(){
        return $this->currentPath;
    }
    
    public function isDev(){
        return $this->mode != 'prod';
    }
    
    public function isProd(){
        return $this->mode === 'prod';
    }
    
    public function isMode($mode){
        return $this->mode === $mode;
    }


    public function register(){
        // config
        $this->mode   = strtolower($this->config->getString('app.mode', 'dev'));
        define('IS_PROD', $this->isProd());
        define('IS_DEV', $this->isDev());
        define('IS_CORE_DEBUG', $this->config->getBoolean('core.debug'));

        define('APP_MODE', $this->mode);     
        $this->config->setEnv( $this->mode );
        
        define('APP_PUBLIC_PATH', $this->config->get('app.public', '/public/' . $this->name . '/'));
        $this->secret = $this->config->getString('app.secret');
        if ( !$this->secret ){
            throw new ConfigurationReadException($this->config, '`app.secret` must be set as random string');
        }
        
        // temp
        Core::setTempDir( $this->getPath() . 'tmp/' );
        
        // cache
        if ( $this->config->getBoolean('cache.enable', true) ){
            $done = false;
            $detect = $this->config->getArray('cache.detect');
            foreach ($detect as $el){
                $class = '\\framework\\cache\\' . $el . 'Cache';
                if ( $class::canUse() ){
                    DI::define('Cache', $class, true);
                    $done = true;
                    break;
                }
            }
            
            if ( !$done )
                DI::define('Cache', '\\framework\\cache\\DisableCache', true);
            
        } else {
            DI::define('Cache', '\\framework\\cache\\DisableCache', true);
        }
        
        // classloader
        $this->_registerLoader();
        
        // template
        //$this->_registerTemplates();
        
        // modules
        $this->_registerModules();
        
        // route
        $this->_registerRoute();

        if (IS_DEV)
            $this->_registerTests();
    }

    private function _registerTests(){
        $this->router->addRoute('*', '/@test', 'framework.test.Tester.run');
        $this->router->addRoute('GET', '/@test.json', 'framework.test.Tester.runAsJson');
    }
    
    private function _registerModules(){
        $modules = $this->config->getArray('app.modules');
        foreach ($modules as $module){
            modules\AbstractModule::register($module);
        }
    }

    private function _registerLoader(){
        $this->classLoader = new ClassLoader();
        $this->classLoader->addNamespace('', $this->getPath() . 'app/');
        
        $this->classLoader->register();
    }

    private function _registerRoute(){
        // routes
        $cache     = c('Cache');
        $isCached  = IS_PROD && $cache->isFast();
        $routeFile = new File($this->getPath() . 'conf/route');
        
        if ( $isCached ){
            $lastUpd      = $cache->get('$.system.route.upd', 0);
            if ( $lastUpd === 0 || $lastUpd != $routeFile->lastModified() ){
                $this->router = null;
                $cache->remove('$.system.routes');
                $cache->set('$.system.route.upd', $lastUpd);
            } else {
                $this->router = $cache->get('$.system.route');
            }
        }
        
        if ( $this->router === null ){
            
            $routeFiles   = array();
            foreach (modules\AbstractModule::$modules as $name => $module){
                $routeFiles['\\modules\\' . $name . '\\controllers\\'] = $module->getRouteFile();
            }
            
            $routeFiles[] = $routeFile;
            
            $routeConfig  = new RouterConfiguration($routeFiles);
            $this->router = new mvc\route\Router();
            $this->router->applyConfig($routeConfig);
            
            if ( $isCached )
                $cache->set('$.system.route', $this->router, '2m');
        }
    }

    
    /**
     * @return Project
     */
    public static function current(){
        return Core::$__project;
    }

    private static $srcDir = null;
    public static function getSrcDir(){
        if ( self::$srcDir ) return self::$srcDir;

        return self::$srcDir = str_replace(DIRECTORY_SEPARATOR, '/', realpath('apps/'));
    }
}