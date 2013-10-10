<?php
namespace regenix\template\tags;

use regenix\core\Regenix;
use regenix\exceptions\CoreStrictException;
use regenix\template\RegenixTemplate;
use regenix\template\RegenixTemplateTag;

class RegenixPublicTag implements RegenixTemplateTag {

    function getName(){
        return 'file';
    }

    public function call($args, RegenixTemplate $ctx){
        $app =  Regenix::app();
        $file = '/public/' . $app->getName() . '/' . $args['_arg'];
        if (APP_MODE_STRICT){
            if (!file_exists(ROOT . $file))
                throw new CoreStrictException('File `%s` not found, at `file` tag', $file);
        }

        return $file;
    }
}
