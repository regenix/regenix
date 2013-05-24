<?php
namespace framework\mvc\template {

use framework\Core;
use framework\Project;
use framework\SDK;
use framework\exceptions\CoreException;
use framework\exceptions\CoreStrictException;
use framework\io\File;
use framework\io\FileNotFoundException;
use framework\lang\String;
use framework\libs\Captcha;
use framework\libs\RegenixTPL\RegenixTemplate as RegenixTPL;
use framework\libs\RegenixTPL\RegenixTemplateTag;
use framework\libs\ImageUtils;
use framework\mvc\route\Router;
use framework\widgets\Widget;

class RegenixTemplate extends BaseTemplate {

    const type = __CLASS__;

    const ENGINE_NAME = 'Regenix Template';
    const FILE_EXT = 'html';

    public static $tpl;
    private static $loaded = false;

    public function __construct($templateFile, $templateName){
        if (!self::$loaded){
            self::$tpl = new RegenixTPL();

            self::$tpl->registerTag(new RegenixDepsAssetsTag());
            self::$tpl->registerTag(new RegenixDepsAssetTag());

            self::$tpl->registerTag(new RegenixAssetTag());
            self::$tpl->registerTag(new RegenixHtmlAssetTag());
            self::$tpl->registerTag(new RegenixPathTag());
            self::$tpl->registerTag(new RegenixPublicTag());

            self::$tpl->registerTag(new RegenixWidgetTag());

            self::$tpl->registerTag(new RegenixImageCropTag());
            self::$tpl->registerTag(new RegenixImageResizeTag());
            self::$tpl->registerTag(new RegenixCaptchaTag());

            self::$tpl->setTempDir( Core::$tempDir . 'regenixtpl/' );
            self::$tpl->setTplDirs( TemplateLoader::getPaths() );
            self::$loaded = true;
        }

        self::$tpl->setFile($templateFile);
        self::$tpl->setRoot(TemplateLoader::getCurrentRoot());
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

            if ($name && !is_file(ROOT . $path)){
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
            $action = $args['_arg'];
            /*if (!$action)
                throw CoreException::formated('Action argument of reverse is empty');*/

            unset($args['_arg']);
            $url = Router::path($action ? TemplateLoader::$CONTROLLER_NAMESPACE . $action : null, $args);

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

    class RegenixWidgetTag extends RegenixTemplateTag {

        function getName(){
            return 'widget';
        }

        public function call($args, RegenixTPL $ctx){
            $name = $args['_arg'];
            unset($args['_arg']);

            $widget = Widget::createByUID($name, $args);
            return $widget->render();
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

    class RegenixDepsAssetsTag extends RegenixTemplateTag {

        function getName(){
            return 'deps.assets';
        }

        public function call($args, RegenixTPL $ctx) {
            $project = Project::current();
            $assets  = $project->getAssets();

            $html     = '';
            $included = array();
            foreach($assets as $group => $dep){
                $html .= RegenixDepsAssetTag::getOne($group, false, $included);
            }
            return $html;
        }
    }

    class RegenixDepsAssetTag extends RegenixTemplateTag {

        function getName(){
            return 'deps.asset';
        }

        public static function getOne($group, $version = false, &$included = array()){
            $project  = Project::current();
            $assets   = $project->getAssetFiles($group, $version, $included);

            $result = '';
            foreach((array)$assets as $file){
                $html = BaseTemplate::getAssetTemplate($file);
                if ($html){
                    $result .= $html . "\n";
                }
            }

            return $result;
        }

        public function call($args, RegenixTPL $ctx) {
            return self::getOne($args['_arg']);
        }
    }

    class RegenixHtmlAssetTag extends RegenixTemplateTag {

        function getName(){
            return 'html.asset';
        }

        public function call($args, RegenixTPL $ctx){
            $file = RegenixAssetTag::get($args['_arg']);
            $tpl  = BaseTemplate::getAssetTemplate($file, $args['ext']);
            if ($tpl)
                return $tpl;

            throw CoreException::formated('Unknown html asset for `%s`', $file);
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

    use framework\libs\I18n;

    function __($message, $args = ''){
        if (is_array($args))
            return I18n::get($message, $args);
        else
            return I18n::get($message, array_slice(func_get_args(), 1));
    }
}