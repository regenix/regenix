<?php
namespace regenix\template\tags;

use regenix\core\Regenix;
use regenix\exceptions\TemplateException;
use regenix\template\RegenixTemplate;
use regenix\template\RegenixTemplateTag;
use regenix\mvc\route\Router;

class RegenixCaptchaTag implements RegenixTemplateTag {

    function getName(){
        return 'image.captcha';
    }

    public function call($args, RegenixTemplate $ctx){
        $app =  Regenix::app();
        if (!$app->config->getBoolean('captcha.enable'))
            throw new TemplateException('Captcha is not enabled in configuration, should be `captcha.enable = on`');

        return Router::path('.regenix.mvc.SystemController.captcha');
    }
}