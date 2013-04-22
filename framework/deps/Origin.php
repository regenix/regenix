<?php
namespace framework\deps;

use framework\io\File;

abstract class Origin {

    protected $env;

    /**
     * @param string $origin
     * @return bool
     */
    abstract public static function isCurrent($origin);

    /**
     * @return int time unix
     */
    abstract public function lastUpdated();

    /**
     * @param $group
     * @return array
     */
    abstract public function getMetaInfo($group);

    /**
     * @param string $group
     * @param string $version
     * @param string $name
     * @param string $toDir
     * @return mixed
     */
    abstract public function downloadDependency($group, $version, $name, $toDir);

    /**
     * @param string $env
     */
    public function setEnv($env){
        $this->env = $env;
    }
}