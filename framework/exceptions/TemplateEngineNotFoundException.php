<?php

namespace regenix\exceptions;

use regenix\lang\CoreException;
use regenix\lang\String;

class TemplateEngineNotFoundException extends CoreException {

    const type = __CLASS__;

    public function __construct($ext) {
        parent::__construct(String::format('Can\'t find template engine for "%s" extension', $ext));
    }
}
