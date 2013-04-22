<?php

namespace framework;

use framework\exceptions\CoreException;
use framework\io\File;

use framework\config\Configuration;
use framework\config\PropertiesConfiguration;
use framework\lang\ModulesClassLoader;
use framework\libs\Captcha;
use framework\modules\AbstractModule;
use framework\mvc\Assets;
use framework\mvc\ModelClassloader;
use framework\mvc\Request;
use framework\mvc\URL;
use framework\mvc\route\RouterConfiguration;
use framework\config\ConfigurationReadException;

use framework\mvc\template\TemplateLoader;

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

    /** @var array */
    public $deps;
    
    /** @var mvc\route\Router */
    public $router;

    /** @var Assets */
    public $assets;

    /**
    * @param string $projectName root directory name of project
    */
    public function __construct($projectName, $inWeb = true){
        $this->name   = $projectName;
        
        $configFile   = $this->getPath() . 'conf/application.conf';
        $this->config = SystemCache::getWithCheckFile('appconf', $configFile);
        
        if ($this->config === null){
            $this->config = new PropertiesConfiguration(new File( $configFile ));
            SystemCache::setWithCheckFile('appconf', $this->config, $configFile);            
        }

        if ($inWeb){
            Request::current();
            $this->applyConfig( $this->config );
        }
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

    /**
     * get public upload directory
     * @return string
     */
    public function getPublicPath(){
        return ROOT . 'public/' . $this->name . '/';
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
            $this->paths[ $path ] = new URL( $path );
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
        $request = Request::current();
        
        foreach ($this->paths as $url){
            if ( $request->isBase( $url ) )
                return $url;
        }
        
        return null;
    }


    public function setUriPath(URL $url){
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
        Request::current();

        // config
        $this->mode   = strtolower($this->config->getString('app.mode', 'dev'));
        define('IS_PROD', $this->isProd());
        define('IS_DEV', $this->isDev());
        define('IS_CORE_DEBUG', $this->config->getBoolean('core.debug'));
        define('APP_MODE_STRICT', $this->config->getBoolean('app.mode.strict', IS_DEV));

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
        /*if ( $this->config->getBoolean('cache.enable', true) ){
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
        }*/
        
        // classloader
        $this->_registerLoader();

        // module deps
        $this->_registerDeps();
        
        // template
        //$this->_registerTemplates();

        // route
        $this->_registerRoute();

        if (IS_DEV)
            $this->_registerTests();

        $this->_registerSystemController();
    }

    private function _registerDeps(){
        $loader = new ModulesClassLoader();
        $loader->register();

        $file = $this->getPath() . 'conf/deps.json';
        if (is_file($file)){
            $this->deps = json_decode(file_get_contents($file), true);
        }

        // assets js, css and other
        $this->assets = new Assets((array)$this->deps['assets']);

        foreach((array)$this->deps['modules'] as $name => $conf){
            AbstractModule::register($name, $conf['version']);
        }

        if (IS_DEV){
            foreach($this->assets->all() as $asset){
                if (!$asset->isExists()){
                    throw CoreException::formated('Asset `%s` not valid or not exists, please run in console `regenix deps update`',
                        $asset->name . ' ' . $asset->patternVersion);
                }
            }
        }
    }

    private function _registerSystemController(){
        if ($this->config->getBoolean('captcha.enable')){
            $this->router->addRoute('GET', Captcha::URL, 'framework.mvc.SystemController.captcha');
        }

        if ($this->config->getBoolean('i18n.js')){
            $this->router->addRoute('GET', '/system/i18n.js', 'framework.mvc.SystemController.i18n_js');
            $this->router->addRoute('GET', '/system/i18n.{_lang}.js', 'framework.mvc.SystemController.i18n_js');
        }
    }

    private function _registerTests(){
        $this->router->addRoute('*', '/@test', 'framework.test.Tester.run');
        $this->router->addRoute('GET', '/@test.json', 'framework.test.Tester.runAsJson');
    }

    private function _registerLoader(){
        $this->classLoader = new ClassLoader();
        $this->classLoader->addClassPath(ROOT . 'vendor/');
        $this->classLoader->addClassLibPath(ROOT . 'vendor/');
        $this->classLoader->addClassPath($this->getPath() . 'app/');

        $this->classLoader->register();
    }

    private function _registerRoute(){
        // routes
        $routeFile = $this->getPath() . 'conf/route';
        $this->router = SystemCache::getWithCheckFile('route', $routeFile);

        if ( $this->router === null ){

            $routeFiles   = array();
            foreach (modules\AbstractModule::$modules as $name => $module){
                $routeFiles['\\modules\\' . $name . '\\controllers\\'] = $module->getRouteFile();
            }
            $routeFiles[] = new File($routeFile);

            $routeConfig  = new RouterConfiguration($routeFiles);
            $this->router = new mvc\route\Router();
            $this->router->applyConfig($routeConfig);
            SystemCache::setWithCheckFile('route', $this->router, $routeFile, 60 * 2);
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

        return self::$srcDir = str_replace(DIRECTORY_SEPARATOR, '/', ROOT . 'apps/');
    }
}