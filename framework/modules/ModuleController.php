<?php

namespace framework\modules;

use framework\mvc\Controller;
use framework\mvc\template\TemplateLoader;

abstract class ModuleController extends Controller {

    const type = __CLASS__;

    /**
     * Short module name
     * @var string
     */
    private $module;


    public function __construct() {
        parent::__construct();
        
        $class  = explode('\\', get_class($this), 3);
        $this->module = $class[1];
        
        TemplateLoader::setAssetPath('/modules/' . $this->module . '/assets/');
    }


    public function render($template = false, array $args = null) {
        if ( $template === false ) {
            $trace      = debug_backtrace();
            $current    = $trace[1];
            
            $class      = explode('\\', $current['class'], 4);
            $controller = $class[3];
            
            $template   = $controller . '/' . $current['function'];
        }
        
        $this->renderTemplate($template, $args);
    }
    
    public function renderTemplate($template, array $args = null) {
        if ( $template[0] != '@' ){            
            $template = $this->module . '/views/' . $template;
        } else {
            // ...
        }
        
        parent::renderTemplate( $template, $args );
    }
}
