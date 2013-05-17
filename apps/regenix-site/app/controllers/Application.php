<?php
namespace controllers;

use framework\cache\Cache;
use framework\libs\I18n;
use models\Group;
use models\User;
use notifiers\ConfirmNotifier;

class Application extends AppController {

    public function page($page = 'index'){

        $group = new Group();
        $group->code = 'admin';
        $group->name = 'Admin';
        $group->save();

        $user = User::find(User::query()->eq('email', 'dz@dim-s.net'))->first();
        var_dump($user->isGroup('admin'));
        dump($user);

        if (!$user)
            $user = new User();

        $user->login = 'dim-s';
        $user->groups = array($group);
        $user->email = 'dz@dim-s.net';
        $user->password = '123456';
        $user->register();

        $lang     = I18n::getLang();
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