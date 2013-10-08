<?php
namespace regenix\template\tags;

use regenix\template\RegenixTemplate;
use regenix\template\RegenixTemplateTag;

class RegenixGetTag implements RegenixTemplateTag {

    function getName(){
        return 'get';
    }

    public function call($args, RegenixTemplate $ctx){
        if (isset($args['default']))
            $ctx->blocks[strtolower($args['_arg'])] = $args['default'];

        return '%__BLOCK_' . strtolower($args['_arg']) . '__%';
    }
}
