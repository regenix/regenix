<?php

namespace framework\mvc\template;

use framework\Core;

class TwigTemplate extends BaseTemplate {
 
    const ENGINE_NAME = 'Twig Template';
    const FILE_EXT = 'twig';

    /** @var \Twig_LoaderInterface */
    private $loader = null;
    
    /** @var \Twig_Environment */
    private $twig = null;
    
    
    private static $loaded = false;

    public function __construct($templateFile, $templateName) {
        
        parent::__construct($templateFile, $templateName);
        
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
        $exts = new TwigExtension($this->twig);
    }
    
    public function render() {
        
        echo $this->twig->render($this->name, $this->args);
    }
}


class TwigExtension {
    
    public function __construct(\Twig_Environment $twig) {
        
        foreach ($this->getFunctions() as $func){
            $twig->addFunction($func);
        }
    }

    public function getFunctions() {
        return array(
            new \Twig_SimpleFunction('path', array($this, 'function_path'))
        );
    }
    
    
    /*** functions ***/
    
    /**
     * reverse routing action to url
     * @param string $arg
     * @return string
     */
    public function function_path($arg, array $args = array()){
        return $arg . '('. implode(', ', $args) .')';
    }
}
