<?php

namespace framework;

use framework\io\File;

use framework\config\Configuration;
use framework\config\PropertiesConfiguration;
use framework\mvc\route\RouterConfiguration;
use framework\config\ConfigurationReadException;

use framework\mvc\template\TemplateLoader;

class Project {

    private $name;
    private $paths = array();
    
    /** @var string */
    private $currentPath;

    /** @var utils\ClassLoader */
    private $classLoader;
    
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
        $this->config = new PropertiesConfiguration( 
                    new File( $this->getPath() . 'conf/application.conf' ) 
                );

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
        return self::getSrcDir() . '/' . $this->getName() . '/';
    }
    
    public function getViewsPath(){
        return self::getPath() . 'app/views/';
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
     * @param Configuration $config
     */
    public function applyConfig(Configuration $config){

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
        return $this->prod === 'prod';
    }


    public function register(){
        
        // config
        $this->mode   = strtolower($this->config->getString('app.mode', 'dev'));
        $this->secret = $this->config->getString('app.secret');
        if ( !$this->secret ){
            throw new ConfigurationReadException($this->config, '`app.secret` must be set as random string');
        }
        
        // routes
        $routeConfig  = new RouterConfiguration(new File($this->getPath() . 'conf/route'));
        $this->router = new mvc\route\Router();
        $this->router->applyConfig($routeConfig);
        
        // classloader
        $this->classLoader = new utils\ClassLoader();
        $this->classLoader->addNamespace('controllers', $this->getPath() . 'app/');
        $this->classLoader->addNamespace('models', $this->getPath() . 'app/');
        
        $this->classLoader->register();
        
        // template
        TemplateLoader::registerPath( $this->getViewsPath() );
        TemplateLoader::register('\framework\mvc\template\PHPTemplate');
        
        TemplateLoader::setDefaultExt( $this->config->getString("template.default", "phtml") );
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

        return self::$srcDir = str_replace(DIRECTORY_SEPARATOR, '/', realpath('src/'));
    }
}