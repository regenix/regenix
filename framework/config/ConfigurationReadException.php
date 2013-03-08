<?php

namespace framework\config;

use framework\exceptions\CoreException;

class ConfigurationReadException extends CoreException {

    private $config;

    public function __construct(Configuration $config, $message){
        parent::__construct($message);
        $this->config = $config;
    }
}
