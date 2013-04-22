<?php
namespace controllers;

use framework\Project;
use framework\deps\GithubOrigin;
use framework\deps\Repository;
use framework\lang\FrameworkClassLoader;
use framework\libs\Captcha;
use framework\libs\I18n;
use framework\libs\WS;
use framework\logger\Logger;
use framework\mvc\Controller;
use framework\mvc\RequestQuery;
use framework\libs\ImageUtils;

class Application extends Controller {

    public function index(){
        $repository = new Repository(Project::current()->deps);
        $repository->setOrigin(new GithubOrigin('https://github.com/dim-s/regenix-repository/'));
        $repository->setEnv('assets');
        $repository->download('jquery', '1.8.*');

        $this->renderJSON('OK');
    }

    public function crop($w, $h){
        $this->renderFile(ImageUtils::crop(ROOT . 'logo.png', $w, $h), false);
    }
}