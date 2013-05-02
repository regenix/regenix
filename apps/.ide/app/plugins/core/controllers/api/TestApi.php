<?php
namespace plugins\core\controllers\api;

use controllers\api\Api;

class TestApi extends Api {

    public function test(){
        $this->renderJSON('ok');
    }
}