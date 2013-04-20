<?php

namespace framework\mvc\template;

use framework\SDK;
use framework\exceptions\CoreException;
use framework\lang\String;
use framework\mvc\providers\ResponseProvider;
use framework\Core;
use framework\Project;

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
    
    /** @var TemplateFunctions[] */
    public static $FUNCTIONS = array();

    public static function __lazyLoad(){
        if ( !self::$lazyLoaded ){
            self::registerFunctions('\framework\mvc\template\extension\StandartTemplateFunctions');
        
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
        }
    }

    /**
     * @param string $name
     * @throws TemplateNotFoundException
     * @throws TemplateEngineNotFoundException
     * @return BaseTemplate
     */
    public static function load($name){
    
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
            throw new TemplateEngineNotFoundException($ext);
        }
        
        $templateFile = self::findFile($name);
        if ( $templateFile === false )
            throw new TemplateNotFoundException( $name );
        
        $template = new $engine($templateFile, $templateName);
        return $template;
    }
    
    public static function getReflection($templateClass){
        
        $reflection = new \ReflectionClass($templateClass);
        
        if ( IS_DEV ){
            if ( !$reflection->isSubclassOf( '\framework\mvc\template\BaseTemplate' ) )
                throw new CoreException(String::format('%s.class must be extends of BaseTemplate', $templateClass));

            if ( !$reflection->isInstantiable() )
                throw new CoreException(String::format('%s.class must be instantiable'));
        }
        
        return $reflection;
    }
    
    public static function switchEngine($templateEngine){
        
        if ( !class_exists($templateEngine) ){
            $templateEngine = '\\framework\\mvc\\template\\' . $templateEngine . 'Template';
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

    public static function register($templateClass, $asDefault = FALSE){
        $reflection = self::getReflection($templateClass);
        $ext = $reflection->getConstant('FILE_EXT');
        
        if ( $asDefault )
            self::setDefaultExt($ext);
        
        self::$engines[ $ext ] = $reflection->getName();
        ResponseProvider::register('\framework\mvc\providers\ResponseBaseTemplateProvider', $templateClass);
        
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

    public static function findFile($file){
        
        foreach(self::$paths as $path){
            
            if (file_exists($path . $file) )
                return $path . $file;
        }
        
        return false;
    }
    
    public static function registerFunctions($funcsClass, $prepend = false){
        
        if ( $prepend )
            array_unshift(self::$FUNCTIONS, $funcsClass);
        else
            self::$FUNCTIONS[] = $funcsClass;
    }
}


TemplateLoader::__lazyLoad();