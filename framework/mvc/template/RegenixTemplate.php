<?php
namespace framework\mvc\template {

use framework\Core;
use framework\Project;
use framework\exceptions\CoreException;
use framework\io\File;
use framework\io\FileNotFoundException;
use framework\lang\ClassLoader;
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
        $path = TemplateLoader::$ASSET_PATH . $args['_arg'];
        $path = str_replace('//', '/', $path);

        if (!file_exists(ROOT . $path)){
            throw new FileNotFoundException(new File($path));
        }

        echo $path;
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

        echo $url;
    }
}

    class RegenixPublicTag extends RegenixTemplateTag {

        function getName(){
            return 'public';
        }

        public function call($args, RegenixTPL $ctx){
            $project = Project::current();
            echo '/public/' . $project->getName() . '/' . $args['_arg'];
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
            echo str_replace(ROOT, '/', $file);
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
            echo str_replace(ROOT, '/', $file);
        }
    }

    class RegenixCaptchaTag extends RegenixTemplateTag {

        function getName(){
            return 'captcha';
        }

        public function call($args, RegenixTPL $ctx){
            $project = Project::current();
            if (!$project->config->getBoolean('captcha.enable'))
                throw CoreException::formated('Captcha is not enable in configuration, needs `captcha.enable = on`');

            echo Captcha::URL;
        }
    }
}

namespace {

    use framework\StrictObject;
    use framework\exceptions\CoreException;
    use framework\mvc\template\RegenixTemplate;

    class TPL extends StrictObject {

        public static function __callStatic($name, $args){
            if ( RegenixTemplate::$tpl ){
                ob_start();
                if (sizeof($args) == 1 && $args[0])
                    $_args = array('_arg' => $args[0]);
                else if (sizeof($args) == 2 && $args[0] && is_array($args[1])){
                    $_args = $args[1];
                    $_args['_arg'] = $args[0];
                } else
                    $_args = (array)$args[0];

                RegenixTemplate::$tpl->_renderTag(strtolower($name), $_args);
                $result = ob_get_contents();
                ob_end_clean();
                return $result;
            } else
                throw CoreException::formated('TPL class may only be used in templates');
        }
    }
}