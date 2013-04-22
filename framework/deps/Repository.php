<?php

namespace framework\deps;


class Repository {

    private static $envList = array('assets', 'modules');

    /** @var array */
    protected $meta;

    /**
     * @var Origin
     */
    protected $origin;

    /**
     * @var array
     */
    protected $deps = array();

    /**
     * @var string
     */
    protected $env;


    public function __construct(array $deps){
        $this->deps = $deps;
    }

    public function setOrigin(Origin $origin){
        $this->origin = $origin;
    }

    public function setEnv($env){
        $this->env = $env;
        $this->origin->setEnv($env);
    }

    protected function getMeta($group){
        if ($this->meta[$group])
            return $this->meta[$group];

        return $this->meta[$group] = $this->origin->getMetaInfo($group);
    }

    protected function findVersion($group, $patternVersion){
        $meta = $this->getMeta($group);
        $curVersion = false;
        foreach($meta as $version => $dep){
            if (preg_match('#^' . $patternVersion . '$#', $version)){
                if ($curVersion === false || version_compare($version, $curVersion, '>')){
                    $curVersion = $version;
                }
            }
        }
        $result = $meta[$curVersion];
        $result['version'] = $curVersion;

        return $result;
    }

    public function download($group, $patternVersion){
        $dep = $this->findVersion($group, $patternVersion);
        if (!$dep){
            throw new DependencyNotFoundException($this->env, $group, $patternVersion);
        } else {
            $toDir = ROOT . $this->env . '/' . $group . '~' . $dep['version'] . '/';
            foreach($dep['files'] as $file){
                $done = $this->origin->downloadDependency($group, $dep['version'], $file, $toDir);
                if (!$done){
                    throw new DependencyDownloadException($this->env, $group, $dep['version']);
                }
            }
            return true;
        }
    }
}