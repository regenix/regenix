<?php
namespace framework\mvc;

use framework\libs\Captcha;

class SystemController extends Controller {

    public function captcha(){
        $this->render(Captcha::current());
    }
}