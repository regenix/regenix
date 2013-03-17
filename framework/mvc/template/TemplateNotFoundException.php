<?php

namespace framework\mvc\template;

use framework\exceptions\CoreException;
use framework\lang\String;

class TemplateNotFoundException extends CoreException {

    const type = __CLASS__;
    
    public function __construct($name) {
        parent::__construct(String::format('Template "%s" not found', $name));
    }
}
