<?php

namespace framework\mvc\template;

use framework\Core;

class TwigTemplate extends BaseTemplate {

    const type = __CLASS__;

    const ENGINE_NAME = 'Twig Template';
    const FILE_EXT = 'twig';

    /** @var \Twig_LoaderInterface */
    private $loader = null;
    
    /** @var \Twig_Environment */
    private $twig = null;
    
    
    private static $loaded = false;

    public function __construct($templateFile, $templateName) {
        
        if ( !self::$loaded ){
            require 'framework/libs/Twig/Autoloader.php';
            \Twig_Autoloader::register();  
            
            self::$loaded = true;
        }
        
        $this->loader = new \Twig_Loader_Filesystem(TemplateLoader::getPaths());
        $options = array();
        if ( IS_PROD ){
            // TODO fix
            //$options['cache'] = Core::$tempDir . 'templates/twig/';
            @mkdir( Core::$tempDir . 'templates/twig/', 0777, true );
        }
        
        $options['debug'] = IS_DEV;
        $this->twig = new \Twig_Environment($this->loader, $options);
        parent::__construct($templateFile, $templateName);
    }
    
    public function render() {
        
        echo $this->twig->render($this->name, $this->args);
    }

    public function registerFunction($name, $callback, $className) {
        
        $this->twig->addFunction($name, new \Twig_SimpleFunction($name, 
                function($arg, array $args = array()) use ($callback){
                    $args['_arg'] = $arg;
                    return call_user_func( $callback, $args );
                }
        , array('pre_escape' => false, 'preserves_safety' => false)));
        
    }
}