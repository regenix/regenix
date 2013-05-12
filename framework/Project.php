<?php

namespace framework;

use framework\deps\Repository;
use framework\exceptions\CoreException;
use framework\exceptions\JsonFileException;
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

    /** @var Repository */
    public $repository;

    /** @var AbstractBootstrap */
    public $bootstrap;

    /** @var array */
    protected $assets;

    /**
     * @param string $projectName root directory name of project
     * @param bool $inWeb
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
            $port = $this->config->getNumber('http.port', 0);
            if ($port){
                Request::current()->setPort($port);
            } else
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
        return self::getSrcDir() . $this->name . '/';
    }
    
    public function getViewPath(){
        return self::getPath() . 'app/views/';
    }

    public function getModelPath(){
        return self::getPath() . 'app/models/';
    }

    public function getTestPath(){
        return self::getPath() . 'tests/';
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

        if (is_file($file = $this->getPath() . 'app/Bootstrap.php')){
            require $file;
            if (!class_exists('Bootstrap', false)){
                throw CoreException::formated('Can`t find `Bootstrap` class at `%s`', $file);
            }
            $this->bootstrap = new \Bootstrap();
        }

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

        // route
        $this->_registerRoute();

        // module deps
        $this->_registerDeps();

        if (IS_DEV)
            $this->_registerTests();

        $this->_registerSystemController();

        if ($this->bootstrap){
            $this->bootstrap->onStart();
        }
    }

    public function loadDeps(){
        $file = $this->getPath() . 'conf/deps.json';
        $this->deps = array();

        if (is_file($file)){
            if (IS_DEV){
                $this->deps = json_decode(file_get_contents($file), true);
                if (json_last_error()){
                    throw new JsonFileException('conf/deps.json');
                }
            } else {
                $this->deps = SystemCache::getWithCheckFile('app.deps', $file);
                if ($this->deps === null){
                    $this->deps = json_decode(file_get_contents($file), true);
                    if (json_last_error()){
                        throw new JsonFileException('conf/deps.json', 'invalid json format');
                    }
                    SystemCache::setWithCheckFile('app.deps', $this->deps, $file, 60 * 5);
                }
            }
        }
    }

    /**
     * Get all assets of project
     * @return array
     * @throws
     */
    public function getAssets(){
        if (is_array($this->assets))
            return $this->assets;

        $this->assets = $this->repository->getAll('assets');

        if (IS_DEV){
            foreach($this->assets as $group => $versions){
                foreach($versions as $version => $el){
                    if (!$this->repository->isValid($group, $version)){
                        throw CoreException::formated('Asset `%s/%s` not valid or not exists, please run in console `regenix deps update`', $group, $version);
                    }
                }
            }
        }
        return $this->assets;
    }

    private function _registerDeps(){
        $loader = new ModulesClassLoader();
        $loader->register();

        $this->loadDeps();
        $this->repository = new Repository($this->deps);

        // modules
        $this->repository->setEnv('modules');
        foreach((array)$this->deps['modules'] as $name => $conf){
            $dep = $this->repository->findLocalVersion($name, $conf['version']);
            if (!$dep){
                throw CoreException::formated('Can`t find `%s/%s` module, please run in console `regenix deps update`', $name, $conf['version']);
            } elseif (IS_DEV && !$this->repository->isValid($name, $dep['version'])){
                throw CoreException::formated('Module `%s` not valid or not exists, please run in console `regenix deps update`', $name);
            }
            AbstractModule::register($name, $dep['version']);
        }

        if (IS_DEV)
            $this->getAssets();
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
        $this->classLoader->addNamespace('tests\\', $this->getPath());

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