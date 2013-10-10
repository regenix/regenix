<?php
namespace regenix\template\tags;

use regenix\core\Regenix;
use regenix\mvc\template\BaseTemplate;
use regenix\template\RegenixTemplate;
use regenix\template\RegenixTemplateTag;

class RegenixDepsAssetTag implements RegenixTemplateTag {

    function getName(){
        return 'deps.asset';
    }

    private static $alreadyIncluded = array();

    public static function getOne($group, $version = false, &$included = array()){
        $app  = Regenix::app();

        //$info = $app->getAsset($group, $version);
        $assets = $app->getAssetFiles($group, $version, $included);
        $result = '';
        foreach((array)$assets as $file){
            $html = BaseTemplate::getAssetTemplate($file);
            if ($html){
                $result .= $html . "\n";
            }
        }

        return $result;
    }

    public function call($args, RegenixTemplate $ctx) {
        $alreadyIncluded =& self::$alreadyIncluded;
        $group = $args['_arg'];

        if ($args['all']){
            $tmp = array();
            $result = self::getOne($group, false, $tmp);
            $alreadyIncluded = array_merge($alreadyIncluded, $tmp);
        } else {
            if ($alreadyIncluded[$group])
                return "";
            $result = self::getOne($group, false, $alreadyIncluded);
        }
        return $result;
    }
}
