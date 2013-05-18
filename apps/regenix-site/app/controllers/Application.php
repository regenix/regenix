<?php
namespace controllers;

use framework\cache\Cache;
use framework\libs\I18n;
use models\Group;
use models\User;
use notifiers\ConfirmNotifier;

class Application extends AppController {

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