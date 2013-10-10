<?php
namespace regenix\template\tags;

use regenix\core\Regenix;
use regenix\template\RegenixTemplate;
use regenix\template\RegenixTemplateTag;

class RegenixDepsAssetsTag implements RegenixTemplateTag {

    function getName(){
        return 'deps.assets';
    }

    public function call($args, RegenixTemplate $ctx) {
        $app =  Regenix::app();
        $assets  = $app->getAssets();

        $html     = '';
        $included = array();
        foreach($assets as $group => $dep){
            $html .= RegenixDepsAssetTag::getOne($group, false, $included);
        }
        return $html;
    }
}
