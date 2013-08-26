<?php
namespace regenix\mvc\template {

use regenix\Regenix;
use regenix\Application;
use regenix\SDK;
use regenix\lang\CoreException;
use regenix\exceptions\CoreStrictException;
use regenix\lang\File;
use regenix\lang\FileNotFoundException;
use regenix\lang\String;
use regenix\libs\Captcha;
use regenix\libs\RegenixTemplate as RegenixTPL;
use regenix\libs\RegenixTemplateTag;
use regenix\libs\ImageUtils;
    use regenix\libs\TemplateException;
    use regenix\mvc\route\Router;

class RegenixTemplate extends BaseTemplate {

    const type = __CLASS__;

    const ENGINE_NAME = 'Regenix Template';
    const FILE_EXT = 'html';

    public static $tpl;
    private static $loaded = false;

    public function __construct($templateFile, $templateName){
        if (!self::$loaded){
            self::$tpl = new RegenixTPL();
            self::$tpl->setTempDir( Regenix::getTempPath() . 'regenixtpl/' );
            self::$tpl->setTplDirs( TemplateLoader::getPaths() );
            self::$loaded = true;
        }

        self::$tpl->setFile($templateFile);
        self::$tpl->setRoot(TemplateLoader::getCurrentRoot());
    }

    public function render(){
        self::$tpl->render( $this->args, REGENIX_IS_DEV !== true );
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

            if ($name && !file_exists(ROOT . $path)){
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
                throw new CoreException('Action argument of reverse is empty');*/

            unset($args['_arg']);
            $action = $action ? ($action[0] != '.' ? TemplateLoader::$CONTROLLER_NAMESPACE . $action : $action) : null;
            $url = Router::path($action, $args);

            if ($url === null)
                throw new TemplateException('Can`t reverse url for action "%s(%s)"',
                    $action, implode(', ', array_keys($args)));

            return $url;
        }
    }

    class RegenixPublicTag extends RegenixTemplateTag {

        function getName(){
            return 'file';
        }

        public function call($args, RegenixTPL $ctx){
            $app =  Regenix::app();
            $file = '/public/' . $app->getName() . '/' . $args['_arg'];
            if (APP_MODE_STRICT){
                if (!file_exists(ROOT . $file))
                    throw new CoreStrictException('File `%s` not found, at `file` tag', $file);
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
            $app =  Regenix::app();
            if (!$app->config->getBoolean('captcha.enable'))
                throw new TemplateException('Captcha is not enabled in configuration, should be `captcha.enable = on`');

            return Captcha::URL;
        }
    }

    class RegenixDepsAssetsTag extends RegenixTemplateTag {

        function getName(){
            return 'deps.assets';
        }

        public function call($args, RegenixTPL $ctx) {
            $app =  Regenix::app();
            $assets  = $app->getAssets();

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
            $app  = Regenix::app();
            $assets   = $app->getAssetFiles($group, $version, $included);

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

            throw new TemplateException('Unknown html asset for `%s`', $file);
        }
    }

    class RegenixDebugInfoTag extends RegenixTemplateTag {

        function getName(){
            return 'debug.info';
        }

        public function call($args, RegenixTPL $ctx){
            $info   = Regenix::getDebugInfo($args['trace']);
            if ($args['trace'])
                return String::format('<!-- execute: %s ms. -->' . "\n" . '<!-- memory: %s kb. -->' . "\n<!-- trace: \n%s\n-->",
                    round($info['time'], 2), round($info['memory'] / 1024), print_r($info['trace'], true));
            else
                return String::format('<!-- execute: %s ms. -->' . "\n" . '<!-- memory: %s kb. -->',
                    round($info['time'], 2), round($info['memory'] / 1024));
        }
    }
}

namespace {

    use regenix\lang\StrictObject;
    use regenix\lang\CoreException;
    use regenix\libs\TemplateException;
    use regenix\mvc\template\RegenixTemplate;

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
                throw new TemplateException('TPL class can be used in templates only');
        }
    }

    use regenix\libs\I18n;

    function __($message, $args = ''){
        if (is_array($args))
            return I18n::get($message, $args);
        else
            return I18n::get($message, array_slice(func_get_args(), 1));
    }
}