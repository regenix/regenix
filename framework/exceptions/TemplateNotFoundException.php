<?php

namespace regenix\exceptions;

use regenix\lang\CoreException;
use regenix\lang\String;

class TemplateNotFoundException extends CoreException {

    const type = __CLASS__;
    
    public function __construct($name) {
        parent::__construct(String::format('Template "%s" not found', $name));
    }
}
