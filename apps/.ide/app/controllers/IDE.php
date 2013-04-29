<?php
namespace controllers;

use framework\mvc\Controller;
use ide\FileType;

class IDE extends Controller {

    public function index(){
        $type = FileType::getFileType(null, '.css');
        $this->put('type', $type);
        $this->put('editor', $type->getEditor());

        $this->render();
    }
}