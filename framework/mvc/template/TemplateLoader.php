<?php

namespace regenix\mvc\template;

use regenix\SDK;
use regenix\lang\CoreException;
use regenix\lang\String;
use regenix\mvc\providers\ResponseProvider;
use regenix\Core;
use regenix\Project;

class TemplateLoader {

    const type = __CLASS__;

    private static $lazyLoaded = false;

    /**
     * @var string
     */
    private static $default;
    
    private static $engines = array();
    private static $registered = array();

    /** @var array */
    private static $paths = array();
    
    /** @var string */
    public static $ASSET_PATH;

    /** @var string */
    public static $CONTROLLER_NAMESPACE = '.controllers.';

    public static function __lazyLoad(){
        if ( !self::$lazyLoaded ){

            self::registerPath(ROOT . 'modules/', false);
            self::registerPath(Core::getFrameworkPath() . 'views/', false);
            
            // current project
            $project = Project::current();
            if ($project){
                self::setAssetPath('/apps/' . $project->getName() . '/assets/');

                $default = $project->config->getString('template.default', 'Regenix');
                $classTemplate = $default;

                self::switchEngine($classTemplate);
                self::registerPath( $project->getViewPath() );
            } else {
                self::switchEngine('Regenix');
            }
        
            self::$lazyLoaded = true;

            if ($project->bootstrap) {
                $project->bootstrap->onUseTemplates();
            }
        }
    }

    /**
     * @param string $name
     * @param bool $throws
     * @throws TemplateNotFoundException
     * @throws TemplateEngineNotFoundException
     * @return BaseTemplate
     */
    public static function load($name, $throws = true){
        $name   = str_replace('\\', '/', $name);
        
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        if ( !$ext && self::$default ){
            $ext  = self::$default;
            if ( $ext )
                $name .= '.' . $ext;
        }
        
        $templateName = $name;
        
        $engine = self::$engines[ $ext ];
        if ( !$engine ){
            if ($throws)
                throw new TemplateEngineNotFoundException($ext);
            else
                return null;
        }
        
        $templateFile = self::findFile($name);
        if ( $templateFile === false ){
            if ($throws)
                throw new TemplateNotFoundException( $name );
            else
                return null;
        }
        
        $template = new $engine($templateFile, $templateName);
        return $template;
    }
    
    public static function getReflection($templateClass){
        $reflection = new \ReflectionClass($templateClass);
        
        if ( IS_DEV ){
            if ( !$reflection->isSubclassOf( '\regenix\mvc\template\BaseTemplate' ) )
                throw new CoreException(String::format('%s.class must be extends of BaseTemplate', $templateClass));

            if ( !$reflection->isInstantiable() )
                throw new CoreException(String::format('%s.class must be instantiable'));
        }
        
        return $reflection;
    }
    
    public static function switchEngine($templateEngine){
        if ( !class_exists($templateEngine) ){
            $templateEngine = '\\regenix\\mvc\\template\\' . $templateEngine . 'Template';
        }
            
        if ( self::$registered[ $templateEngine ] ){
            $ext = self::getReflection($templateEngine);
            self::setDefaultExt($ext->getConstant('FILE_EXT'));
        } else {
            TemplateLoader::register($templateEngine, true);
        }
    }
    
    public static function setAssetPath($path){
        self::$ASSET_PATH = $path;
    }

    public static function setControllerNamespace($namespace){
        self::$CONTROLLER_NAMESPACE = $namespace;
    }

    public static function register($templateClass, $asDefault = FALSE){
        $reflection = self::getReflection($templateClass);
        $ext = $reflection->getConstant('FILE_EXT');
        
        if ( $asDefault )
            self::setDefaultExt($ext);
        
        self::$engines[ $ext ] = $reflection->getName();
        ResponseProvider::register('\regenix\mvc\providers\ResponseBaseTemplateProvider', $templateClass);
        
        self::$registered[ $templateClass ] = 1;
        SDK::trigger('registerTemplateEngine', array($reflection));
    }

    public static function setDefaultExt($ext){
        
        self::$default = $ext;
    }
    
    public static function registerPath($path, $prepend = true){
        if ( $prepend )
            array_unshift(self::$paths, $path);
        else
            self::$paths[] = $path;
    }
    
    public static function getPaths(){
        return self::$paths;
    }

    private static $currentRoot = false;

    public static function getCurrentRoot(){
        return self::$currentRoot;
    }

    public static function findFile($file){
        foreach(self::$paths as $path){
            if (file_exists($path . $file) ){
                self::$currentRoot = $path;
                return $path . $file;
            }
        }
        
        return false;
    }
}


TemplateLoader::__lazyLoad();