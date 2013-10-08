<?php
namespace regenix\template\tags;

use regenix\exceptions\TemplateException;
use regenix\lang\DI;
use regenix\template\RegenixTemplate;

class RegenixTagAdapter {

    const type = __CLASS__;

    private function __construct(){}

    public static function __callStatic($name, $args){
        $tpl = DI::getInstance(RegenixTemplate::type);
        if ( $tpl ){
            if (sizeof($args) == 1 && $args[0])
                $_args = array('_arg' => $args[0]);
            else if (sizeof($args) == 2 && $args[0] && is_array($args[1])){
                $_args = $args[1];
                $_args['_arg'] = $args[0];
            } else
                $_args = (array)$args[0];

            ob_start();
            try {
                $tpl->renderTag(strtolower($name), $_args);
                $result = ob_get_contents();
                ob_end_clean();
            } catch (\Exception $e){
                ob_end_clean();
                throw $e;
            }

            return $result;
        } else
            throw new TemplateException('TPL class can be used in templates only');
    }
}
