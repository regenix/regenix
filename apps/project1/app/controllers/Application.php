<?php
namespace controllers;

use framework\Project;
use framework\lang\FrameworkClassLoader;
use framework\libs\Captcha;
use framework\libs\I18n;
use framework\logger\Logger;
use framework\mvc\Controller;
use framework\mvc\RequestQuery;
use framework\libs\ImageUtils;
use models\Log;

class Application extends Controller {

    public function index(){
        $query = Log::query()->field("upd")->exists(false);
        $logs  = Log::find($query)->explain();

        dump($logs);
        $this->render();
    }

    public function crop($w, $h){
        $this->renderFile(ImageUtils::crop(ROOT . 'logo.png', $w, $h), false);
    }
}