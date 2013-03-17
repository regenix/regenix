<?php

namespace framework\config;

use framework\exceptions\CoreException;

class ConfigurationReadException extends CoreException {

    const type = __CLASS__;

    private $config;

    public function __construct(Configuration $config, $message){
        parent::__construct($message);
        $this->config = $config;
    }
}
