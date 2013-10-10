<?php
namespace regenix\template\tags;

use regenix\exceptions\TemplateNotFoundException;
use regenix\lang\DI;
use regenix\mvc\http\Request;
use regenix\mvc\http\session\Flash;
use regenix\mvc\http\session\Session;
use regenix\mvc\template\TemplateLoader;
use regenix\template\RegenixTemplate;
use regenix\template\RegenixTemplateTag;

class RegenixRenderTag implements RegenixTemplateTag {

    function getName(){
        return 'render';
    }

    protected function render($args, RegenixTemplate $tpl){
        $tplFile = $args['_arg'];

        $args['flash']   = DI::getInstance(Flash::type);
        $args['request'] = DI::getInstance(Request::type);
        $args['session'] = DI::getInstance(Session::type);

        $file = TemplateLoader::findFile($tplFile);
        if (!$file)
            throw new TemplateNotFoundException($tplFile);

        $tpl->setFile( $file );

        ob_start();
        $tpl->render($args);
        $str = ob_get_contents();
        ob_end_clean();

        return $str;
    }

    public function call($args, RegenixTemplate $ctx){
        return $this->render($args, $ctx->duplicate());
    }
}