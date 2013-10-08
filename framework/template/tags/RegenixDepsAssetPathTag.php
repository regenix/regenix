<?php
namespace regenix\template\tags;

use regenix\core\Regenix;
use regenix\template\RegenixTemplate;
use regenix\template\RegenixTemplateTag;

class RegenixDepsAssetPathTag implements RegenixTemplateTag {

    function getName(){
        return 'deps.asset.path';
    }

    public function call($args, RegenixTemplate $ctx){
        $group = $args['_arg'];
        $version = $args['version'];
        $info  =  Regenix::app()->getAsset($group, $version);

        return $path   = '/assets/' . $group . '~' . $info['version'] . '/';
    }
}