<?php

namespace regenix\exceptions;

use regenix\lang\CoreException;

class ActionNotFoundException extends CoreException {
    public function __construct($action){
        parent::__construct('In routes: the action "%s" does not exist', $action);
    }
}