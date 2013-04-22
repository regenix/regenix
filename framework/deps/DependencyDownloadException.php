<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dim-s
 * Date: 22.04.13
 * Time: 17:45
 * To change this template use File | Settings | File Templates.
 */

namespace framework\deps;

use framework\exceptions\CoreException;
use framework\lang\String;

class DependencyDownloadException extends CoreException {

    public $env;
    public $group;
    public $version;

    public function __construct($env, $group, $version){
        $this->env = $env;
        $this->group = $group;
        $this->version = $version;
        parent::__construct(String::format('Can`t download `%s` dependency `%s`, version - %s', $env, $group, $version));
    }
}