<?php
namespace framework\mvc\template\extension;

use framework\mvc\template\TemplateFunctions;
use framework\mvc\template\TemplateLoader;

abstract class StandartTemplateFunctions extends TemplateFunctions {
    
    final public static function asset($args){
        return TemplateLoader::$ASSET_PATH . 
                ($args['_arg'] ? $args['_arg'] : $args['file']);
    }

    final public static function src($args){
        
        return APP_PUBLIC_PATH . ($args['_arg'] ? $args['_arg'] : $args['file']);
    }

    final public static function path($args){
        
        $action = $args['_arg'] ? $args['_arg'] : $args['action'];
        $params = $args['args'];
        if ( !$params ){
            unset($args['_arg'], $args['action']);
            $params = $args;
        }
        
        return $action . '(' . implode(',', $params) .')';
    }
}
