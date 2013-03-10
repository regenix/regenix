<?php

namespace framework\mvc\template;

use framework\exceptions\CoreException;
use framework\utils\StringUtils;
use framework\utils\FileUtils;
use framework\mvc\providers\ResponseProvider;

class TemplateLoader {

    /**
     * @var string
     */
    private static $default;
    
    private static $engines;

    /** @var array */
    private static $paths = array();

    /**
     * @param string $name
     * @return BaseTemplate
     */
    public static function load($name){
    
        $name   = str_replace('\\', '/', $name);
        
        $ext = FileUtils::getExtension($name);
        if ( !$ext && self::$default ){
            $ext  = self::$default;
            if ( $ext )
                $name .= '.' . $ext;
        }
        
        $templateName = $name;
        
        $engine = self::$engines[ $ext ];
        if ( !$engine )
            throw new TemplateEngineNotFoundException($ext);
        
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
                throw new CoreException(StringUtils::format('%s.class must be extends of BaseTemplate', $templateClass));

            if ( !$reflection->isInstantiable() )
                throw new CoreException(StringUtils::format('%s.class must be instantiable'));
        }
        
        return $reflection;
    }

    public static function register($templateClass, $asDefault = FALSE){
    
        $reflection = self::getReflection($templateClass);
        $ext = $reflection->getConstant('FILE_EXT');
        
        if ( $asDefault )
            self::setDefaultExt($ext);
        
        self::$engines[ $ext ] = $reflection->getName();
        ResponseProvider::register('\framework\mvc\providers\ResponseBaseTemplateProvider', $templateClass);
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
}
