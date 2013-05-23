<?php
namespace controllers;

use framework\Project;
use framework\cache\Cache;
use framework\lang\ClassFileScanner;
use framework\lang\ClassScanner;
use framework\libs\I18n;
use framework\widgets\Widget;

class Application extends AppController {

    public function test(){
        $meta = ClassScanner::find('controllers\\Application');
        dump($meta->getFilename());

        $this->ok();
    }

    public function page($page = 'index'){
        $lang = I18n::getLang();
        if ($lang === 'default' || $lang === 'en')
            $lang = '';

        $template = $this->actionMethodReflection->getDeclaringClass()->getShortName()
            . '/' . $lang . '/' . $page . '.html';
        if (!$this->templateExists($template))
            $template = $this->actionMethodReflection->getDeclaringClass()->getShortName() . '/' . $page . '.html';

        if (!$this->templateExists($template))
            $this->notFound($page);

        $this->put('page', $template);
        $this->render('.page.html');
    }
}