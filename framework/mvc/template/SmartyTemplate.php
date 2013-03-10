<?php

namespace framework\mvc\template;

use framework\Core;

class SmartyTemplate extends BaseTemplate {
 
    const ENGINE_NAME = 'Smarty3 Template';
    const FILE_EXT = 'tpl';

    /** @var \Smarty */
    private static $smarty;


    private static $loaded = false;

    public function __construct($templateFile, $templateName) {
        
        parent::__construct($templateFile, $templateName);
        
        if ( !self::$loaded ){
            require 'framework/libs/Smarty/Smarty.class.php';  
            
            self::$smarty = new \Smarty();
            self::$smarty->debugging = IS_DEV;
            
            $compilerDir = Core::$tempDir . 'templates/smarty/compile/';
            if ( !file_exists($compilerDir) ){
                @mkdir($compilerDir, 0777, true);
            }

            $tempDir = Core::$tempDir . 'templates/smarty/cache/';
            if ( !file_exists($tempDir) ){
                @mkdir($tempDir, 0777, true);
            }

            self::$smarty->setCompileDir($compilerDir);
            self::$smarty->setCacheDir($tempDir);
            
            self::$loaded = true;
        }
        
        self::$smarty->setTemplateDir(TemplateLoader::getPaths());
    }
    
    public function render() {
        
        self::$smarty->clearAllAssign();
        self::$smarty->assign($this->args);
        self::$smarty->display($this->name);
    }
}