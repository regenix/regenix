<?php

namespace framework\mvc\template;

use framework\exceptions\CoreException;
use framework\lang\String;

class TemplateEngineNotFoundException extends CoreException {

    const type = __CLASS__;

    public function __construct($ext) {
        parent::__construct(String::format('Can\'t find template engine for "%s" extension', $ext));
    }
}
