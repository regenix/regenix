<?php

namespace regenix\exceptions;

use regenix\core\Regenix;
use regenix\lang\CoreException;

class ActionNotFoundException extends CoreException {
    public function __construct($action){
        parent::__construct('The action "%s" does not exist', $action);
    }

    public function getSourceFile() {
        $app = Regenix::app();
        return $app->getPath() . 'conf/route';
    }

    public function getSourceLine(){
        return 0;
    }
}