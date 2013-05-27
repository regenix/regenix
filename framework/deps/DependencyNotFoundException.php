<?php
namespace regenix\deps;

use regenix\lang\CoreException;
use regenix\lang\String;

class DependencyNotFoundException extends CoreException {

    public $env;
    public $group;
    public $version;

    public function __construct($env, $group, $version){
        $this->env = $env;
        $this->group = $group;
        $this->version = $version;
        parent::__construct(String::format('Dependency `%s/%s/%s` not found', $env, $group, $version));
    }
}