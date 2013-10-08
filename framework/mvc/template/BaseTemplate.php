<?php
namespace regenix\mvc\template;

use regenix\core\Application;
use regenix\core\Regenix;
use regenix\lang\SystemCache;
use regenix\exceptions\TypeException;
use regenix\lang\File;
use regenix\lang\IClassInitialization;

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

    public function toString(){
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
        $app = Regenix::app();
        if ($app->bootstrap)
            $app->bootstrap->onTemplateRender($this);
    }


    private static $assetsTpls = array();

    public static function registerAssetTemplate($ext, $callback){
        if (REGENIX_IS_DEV === true && !is_callable($callback)){
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

    /**
     * @return array
     */
    public static function getAssetExtensions(){
        return array_keys(self::$assetsTpls);
    }

    public static function initialize(){
        self::registerAssetTemplate('css', function($path, $ext){
            return '<link rel="stylesheet" type="text/css" href="'. $path .'">';
        });
        self::registerAssetTemplate('js', function($path, $ext){
            return '<script type="text/javascript" src="' . $path . '"></script>';
        });

        self::registerAssetTemplate('', function($path, $ext){
            $file     = new File(ROOT . $path);
            $hashName = md5(ROOT . $path);
            $lastMod  = SystemCache::get('gr.assets.mod.' . $hashName);
            $lastHtml = SystemCache::get('gr.assets.html.' . $hashName);

            if ($lastHtml && !$file->isModified($lastMod, REGENIX_IS_DEV)){
                return $lastHtml;
            }

            $files = $file->findFiles(true);

            $extensions = BaseTemplate::getAssetExtensions();
            $contents   = array_fill_keys($extensions, null);

            foreach($files as $file){
                $ext = $file->getExtension();
                if ($ext && array_search($ext, $extensions, true) !== false){
                    $realName = str_replace(ROOT, '', $file->getPath());
                    $contents[$ext] .= "/* File: " . $realName . " */ \n\n" . $file->getContents() . "\n\n";
                }
            }

            $app = Regenix::app();
            $destDir  = $app->getPublicPath() . 'assets/';
            $dir = new File($destDir);
            if (!$dir->exists())
                $dir->mkdirs();

            $return = '<!-- asset group: ' . $path . ', mod: '. date('H:i:s d.m.Y', time()) .' -->' . "\n";
            foreach($contents as $ext => $content){
                if ($content){
                    file_put_contents($fileName = $destDir . $hashName . '.' . $ext, $content);
                    $return .= BaseTemplate::getAssetTemplate(
                        $app->getPublicUri() . 'assets/' . $hashName . '.' . $ext . '?' . time(), $ext
                    );
                }
            }
            $return .= "\n" . '<!-- /asset group -->' . "\n";

            SystemCache::set('gr.assets.mod.' . $hashName, time());
            SystemCache::set('gr.assets.html.' . $hashName, $return);

            return $return;
        });
    }
}