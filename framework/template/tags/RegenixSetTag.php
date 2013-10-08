<?php
namespace regenix\template\tags;

use regenix\exceptions\TemplateException;
use regenix\template\RegenixTemplate;
use regenix\template\RegenixTemplateTag;

class RegenixSetTag implements RegenixTemplateTag {

    function getName(){
        return 'set';
    }

    public function call($args, RegenixTemplate $ctx){
        foreach($args as $key => $value){
            $key = strtolower($key);
            if ($key === 'content')
                throw new TemplateException('Block `content` cannot be used');

            $ctx->blocks[$key] = $value;
        }
    }
}