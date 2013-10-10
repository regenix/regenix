<?php
namespace regenix\template\tags;

use regenix\exceptions\TemplateException;
use regenix\mvc\template\BaseTemplate;
use regenix\template\RegenixTemplate;
use regenix\template\RegenixTemplateTag;
use regenix\template\tags\RegenixAssetTag;

class RegenixHtmlAssetTag implements RegenixTemplateTag {

    function getName(){
        return 'html.asset';
    }

    public function call($args, RegenixTemplate $ctx){
        $file = RegenixAssetTag::get($args['_arg']);
        $tpl  = BaseTemplate::getAssetTemplate($file, $args['ext']);
        if ($tpl)
            return $tpl;

        throw new TemplateException('Unknown html asset for `%s`', $file);
    }
}