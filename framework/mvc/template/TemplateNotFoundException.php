<?php

namespace framework\mvc\template;

use framework\exceptions\CoreException;
use framework\utils\StringUtils;

class TemplateNotFoundException extends CoreException {
    
    
    public function __construct($name) {
        parent::__construct(StringUtils::format('Template "%s" not found', $name));
    }
}
