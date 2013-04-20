<?php
namespace framework\mvc\template {

use framework\Core;
use framework\Project;
use framework\SDK;
use framework\exceptions\CoreException;
use framework\exceptions\CoreStrictException;
use framework\io\File;
use framework\io\FileNotFoundException;
use framework\lang\ClassLoader;
use framework\lang\String;
use framework\libs\Captcha;
use framework\libs\RegenixTPL\RegenixTemplate as RegenixTPL;
use framework\libs\RegenixTPL\RegenixTemplateTag;
use framework\libs\ImageUtils;

ClassLoader::load(RegenixTPL::type);

class RegenixTemplate extends BaseTemplate {

    const type = __CLASS__;

    const ENGINE_NAME = 'Regenix Template';
    const FILE_EXT = 'html';

    public static $tpl;
    private static $loaded = false;

    public function __construct($templateFile, $templateName){

        if (!self::$loaded){
            self::$tpl = new RegenixTPL();

            self::$tpl->registerTag(new RegenixAssetTag());
            self::$tpl->registerTag(new RegenixHtmlAssetTag());
            self::$tpl->registerTag(new RegenixPathTag());
            self::$tpl->registerTag(new RegenixPublicTag());

            self::$tpl->registerTag(new RegenixImageCropTag());
            self::$tpl->registerTag(new RegenixImageResizeTag());
            self::$tpl->registerTag(new RegenixCaptchaTag());

            self::$tpl->setTempDir( Core::$tempDir . 'regenixtpl/' );
            self::$tpl->setTplDirs( TemplateLoader::getPaths() );
            self::$loaded = true;
        }

        self::$tpl->setFile($templateFile);
    }

    public function render(){
        self::$tpl->render( $this->args, IS_PROD );
    }

    public function registerFunction($name, $callback, $className) {
        // nop
    }
}

    class RegenixAssetTag extends RegenixTemplateTag {

        function getName(){
            return 'asset';
        }

        public function call($args, RegenixTPL $ctx){
            return self::get($args['_arg']);
        }

        public static function get($name){
            if (String::startsWith($name, 'http://')
                || String::startsWith($name, 'https://') || String::startsWith($name, '//'))
                return $name;

            $path = TemplateLoader::$ASSET_PATH . $name;
            $path = str_replace('//', '/', $path);

            if (!file_exists(ROOT . $path)){
                throw new FileNotFoundException(new File($path));
            }

            return $path;
        }
    }

    class RegenixPathTag extends RegenixTemplateTag {

        function getName(){
            return 'path';
        }

        public function call($args, RegenixTPL $ctx){
            $action = $args['_arg'] ? $args['_arg'] : $args['action'];
            if (!$action)
                throw CoreException::formated('Action argument of reverse is empty');

            unset($args['_arg'], $args['action']);
            $project = Project::current();
            $url = $project->router->reverse($action, $args);
            if ($url === null)
                throw CoreException::formated('Can`t reverse url for action "%s(%s)"',
                    $action, implode(', ', array_keys($args)));

            return $url;
        }
    }

    class RegenixPublicTag extends RegenixTemplateTag {

        function getName(){
            return 'file';
        }

        public function call($args, RegenixTPL $ctx){
            $project = Project::current();
            $file = '/public/' . $project->getName() . '/' . $args['_arg'];
            if (APP_MODE_STRICT){
                if (!file_exists(ROOT . $file))
                    throw CoreStrictException::formated('File `%s` not found, at `file` tag', $file);
            }

            return $file;
        }
    }

    class RegenixImageCropTag extends RegenixTemplateTag {

        function getName(){
            return 'image.crop';
        }

        public function call($args, RegenixTPL $ctx){
            $file = $args['_arg'];
            if(!file_exists($file))
                $file = ROOT . $file;

            $file = ImageUtils::crop($file, $args['w'], $args['h']);
            return str_replace(ROOT, '/', $file);
        }
    }

    class RegenixImageResizeTag extends RegenixTemplateTag {

        function getName(){
            return 'image.resize';
        }

        public function call($args, RegenixTPL $ctx){
            $file = $args['_arg'];
            if(!file_exists($file))
                $file = ROOT . $file;

            $file = ImageUtils::resize($file, $args['w'], $args['h']);
            return str_replace(ROOT, '/', $file);
        }
    }

    class RegenixCaptchaTag extends RegenixTemplateTag {

        function getName(){
            return 'image.captcha';
        }

        public function call($args, RegenixTPL $ctx){
            $project = Project::current();
            if (!$project->config->getBoolean('captcha.enable'))
                throw CoreException::formated('Captcha is not enable in configuration, needs `captcha.enable = on`');

            return Captcha::URL;
        }
    }

    class RegenixHtmlAssetTag extends RegenixTemplateTag {

        function getName(){
            return 'html.asset';
        }

        public function call($args, RegenixTPL $ctx){
            $file = RegenixAssetTag::get($args['_arg']);
            $ext  = $args['ext'] ? $args['ext'] : strtolower(pathinfo($file, PATHINFO_EXTENSION));

            switch($ext){
                case 'js': {
                    return '<script type="text/javascript" src="' . $file . '"></script>' . "\n";
                }
                case 'dart': {
                    return '<script type="application/dart" src="' . $file . '"></script>';
                }
                case 'coffee': {
                    return '<script type="text/coffeescript" src="' . $file . '"></script>';
                }
                case 'ts': {
                    return '<script type="text/typescript" src="' . $file . '"></script>';
                }
                case 'css': {
                    return '<link rel="stylesheet" type="text/css" href="'. $file .'">' . "\n";
                } break;
            }

            throw CoreException::formated('Unknown html asset extension `%s`, at `%s`', $ext, $file);
        }
    }
}

namespace {

    use framework\exceptions\StrictObject;
    use framework\exceptions\CoreException;
    use framework\mvc\template\RegenixTemplate;

    class TPL extends StrictObject {

        public static function __callStatic($name, $args){
            if ( RegenixTemplate::$tpl ){
                if (sizeof($args) == 1 && $args[0])
                    $_args = array('_arg' => $args[0]);
                else if (sizeof($args) == 2 && $args[0] && is_array($args[1])){
                    $_args = $args[1];
                    $_args['_arg'] = $args[0];
                } else
                    $_args = (array)$args[0];

                ob_start();
                try {
                    RegenixTemplate::$tpl->_renderTag(strtolower($name), $_args);
                    $result = ob_get_contents();
                    ob_end_clean();
                } catch (\Exception $e){
                    ob_end_clean();
                    throw $e;
                }

                return $result;
            } else
                throw CoreException::formated('TPL class may only be used in templates');
        }
    }
}