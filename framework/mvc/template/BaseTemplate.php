<?php
namespace framework\mvc\template;

use framework\Project;
use framework\exceptions\TypeException;
use framework\lang\IClassInitialization;

abstract class BaseTemplate implements IClassInitialization {

    const type = __CLASS__;

    const REGENIX = 'Regenix';
    const PHP = 'PHP';

    protected $file;
    protected $name;
    protected $args = array();

    const ENGINE_NAME = 'abstract';
    const FILE_EXT    = '???';


    public function __construct($templateFile, $templateName) {

        $this->file = $templateFile;
        $this->name = $templateName;
    }

    public function getContent(){ return null; }
    public function render(){}

    public function __toString(){
        $content = $this->getContent();
        if ($content === null){
            ob_start();
            $this->render();
            $content = ob_get_contents();
            ob_end_clean();
        }
        return $content;
    }

    public function putArgs(array $args = array()){
        $this->args = $args;
    }

    public function put($name, $value){
        $this->args[$name] = $value;
    }

    public function onBeforeRender(){
        $project = Project::current();
        if ($project->bootstrap)
            $project->bootstrap->onTemplateRender($this);
    }


    private static $assetsTpls = array();

    public static function registerAssetTemplate($ext, $callback){
        if (IS_DEV && !is_callable($callback)){
            throw new TypeException('$callback', 'callable');
        }

        self::$assetsTpls[strtolower($ext)] = $callback;
    }

    public static function getAssetTemplate($path, $ext = false){
        $ext = strtolower($ext ? $ext : pathinfo($path, PATHINFO_EXTENSION));
        if ($callback = self::$assetsTpls[$ext]){
            return call_user_func($callback, $path, $ext);
        }
        return '';
    }

    public static function initialize(){
        self::registerAssetTemplate('js', function($path, $ext){
            return '<script type="text/javascript" src="' . $path . '"></script>';
        });
        self::registerAssetTemplate('css', function($path, $ext){
            return '<link rel="stylesheet" type="text/css" href="'. $path .'">';
        });
    }
}