<?php

namespace framework\mvc\template;

use framework\exceptions\CoreException;
use framework\utils\StringUtils;

class TemplateEngineNotFoundException extends CoreException {
    
    
    public function __construct($ext) {
        parent::__construct(StringUtils::format('Can\'t find template engine for "%s" extension', $ext));
    }
}
