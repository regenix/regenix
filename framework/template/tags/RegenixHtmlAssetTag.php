<?php
namespace regenix\template\tags;

use regenix\core\Regenix;
use regenix\exceptions\TemplateException;
use regenix\lang\String;
use regenix\mvc\template\BaseTemplate;
use regenix\template\RegenixTemplate;
use regenix\template\RegenixTemplateTag;
use regenix\template\tags\RegenixAssetTag;

class RegenixHtmlAssetTag implements RegenixTemplateTag {

    private $assetTag;
    private $alreadyIncluded = array();

    public function __construct(RegenixAssetTag $assetTag){
        $this->assetTag = $assetTag;
    }

    public function getName(){
        return 'html.asset';
    }

    public function getOne($group, $version = false, &$included = array()){
        $app  = Regenix::app();
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

    public function callDep($group, array $args) {
        $alreadyIncluded =& $this->alreadyIncluded;

        if ($args['all']){
            $tmp = array();
            $result = $this->getOne($group, false, $tmp);
            $alreadyIncluded = array_merge($alreadyIncluded, $tmp);
        } else {
            if ($alreadyIncluded[$group])
                return "";
            $result = $this->getOne($group, false, $alreadyIncluded);
        }
        return $result;
    }

    public function call($args, RegenixTemplate $ctx){
        $name = $args['_arg'];
        if (String::startsWith($name, 'dep:')){
            $name = substr($name, 4);
            return $this->callDep($name, $args);
        }

        $file = $this->assetTag->get($args['_arg']);
        $tpl  = BaseTemplate::getAssetTemplate($file, $args['ext']);
        if ($tpl)
            return $tpl;

        throw new TemplateException('Unknown html asset for `%s`', $file);
    }
}