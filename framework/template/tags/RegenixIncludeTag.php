<?php
namespace regenix\template\tags;

use regenix\template\RegenixTemplate;

class RegenixIncludeTag extends RegenixRenderTag {

    function getName(){
        return 'include';
    }

    public function call($args, RegenixTemplate $ctx){
        $args = array_merge($ctx->getArgs(), $args);
        return $this->render($args, $ctx->duplicate());
    }
}