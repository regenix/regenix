<?php
namespace regenix\mvc\template {

use regenix\core\Regenix;
use regenix\core\Application;
use regenix\lang\CoreException;
use regenix\lang\String;
use regenix\libs\captcha\Captcha;
use regenix\template\RegenixTemplate as RegenixTPL;
use regenix\libs\ImageUtils;
use regenix\mvc\route\Router;

class RegenixTemplate extends BaseTemplate {

    const type = __CLASS__;

    const ENGINE_NAME = 'Regenix Template';
    const FILE_EXT = 'html';

    protected static $tpl;
    protected static $loaded = false;

    public function __construct($templateFile, $templateName){
        parent::__construct($templateFile, $templateName);

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

    public static function current(){
        return self::$tpl;
    }
}
}

namespace {

    use regenix\lang\CoreException;
    use regenix\mvc\template\RegenixTemplate;
    use regenix\i18n\I18n;

    function __($message, $args = ''){
        if (is_array($args))
            return I18n::get($message, $args);
        else
            return I18n::get($message, array_slice(func_get_args(), 1));
    }

}