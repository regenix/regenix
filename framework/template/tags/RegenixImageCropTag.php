<?php
namespace regenix\template\tags;

use regenix\libs\ImageUtils;
use regenix\template\RegenixTemplate;
use regenix\template\RegenixTemplateTag;

class RegenixImageCropTag implements RegenixTemplateTag {

    function getName(){
        return 'image.crop';
    }

    public function call($args, RegenixTemplate $ctx){
        $file = $args['_arg'];
        if(!file_exists($file))
            $file = ROOT . $file;

        $file = ImageUtils::crop($file, $args['w'], $args['h']);
        return str_replace(ROOT, '/', $file);
    }
}
