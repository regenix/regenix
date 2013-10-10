<?php
namespace regenix\template\tags;

use regenix\core\Regenix;
use regenix\lang\String;
use regenix\template\RegenixTemplate;
use regenix\template\RegenixTemplateTag;

class RegenixDebugInfoTag implements RegenixTemplateTag {

    function getName(){
        return 'debug.info';
    }

    public function call($args, RegenixTemplate $ctx){
        $app = Regenix::app();
        $info   = Regenix::getDebugInfo($args['trace']);
        $result = '<!-- app.mode: ' . ($app->isDev() ? 'dev' : 'prod') . ' -->' . "\n";

        if ($args['trace'])
            $result .= String::format('<!-- execute: %s ms. -->' . "\n" . '<!-- memory: %s kb. -->' . "\n<!-- trace: \n%s\n-->",
                round($info['time'], 2), round($info['memory'] / 1024), print_r($info['trace'], true));
        else
            $result .= String::format('<!-- execute: %s ms. -->' . "\n" . '<!-- memory: %s kb. -->',
                round($info['time'], 2), round($info['memory'] / 1024));

        return $result;
    }
}
