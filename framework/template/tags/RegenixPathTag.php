<?php
namespace regenix\template\tags;

use regenix\exceptions\TemplateException;
use regenix\mvc\route\Router;
use regenix\mvc\template\TemplateLoader;
use regenix\template\RegenixTemplate;
use regenix\template\RegenixTemplateTag;

class RegenixPathTag implements RegenixTemplateTag {

    function getName(){
        return 'path';
    }

    public function call($args, RegenixTemplate $ctx){
        $action = $args['_arg'];
        /*if (!$action)
            throw new CoreException('Action argument of reverse is empty');*/

        unset($args['_arg']);
        $action = $action ? ($action[0] != '.' ? TemplateLoader::$CONTROLLER_NAMESPACE . $action : $action) : null;
        $url = Router::path($action, $args);

        if ($url === null)
            throw new TemplateException('Can`t reverse url for action "%s(%s)"',
                $action, implode(', ', array_keys($args)));

        return $url;
    }
}