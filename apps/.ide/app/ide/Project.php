<?php
namespace ide;

use ide;

final class Project {

    /** @var ProjectType */
    public $type;

    /** @var string */
    protected $name;

    public function __construct($name){
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getPath(){
        return \framework\Project::getSrcDir() . $this->name . '/';
    }

    /**
     * @return string
     */
    public function getName(){
        return $this->name;
    }

    private static $projects;

    /**
     * @param bool $cached
     * @return Project[]
     */
    public static function findAll($cached = true){
        if ($cached && self::$projects)
            return self::$projects;

        self::$projects = array();

        $srcDir = \framework\Project::getSrcDir();

        $dirs   = scandir($srcDir);
        foreach($dirs as $dir){
            if ($dir === '..' || $dir === '.' || $dir === '.ide')
                continue;

            if (is_dir($srcDir . $dir)){
                $project = new Project($dir);
                $project->type = ProjectType::getProjectType($project);
                if ($project->type !== null){
                    self::$projects[] = $project;
                }
            }
        }
        return self::$projects;
    }

    /**
     * @param string $name
     * @param bool $cached
     * @return Project|null
     */
    public static function findByName($name, $cached = true){
        $projects = self::findAll($cached);
        foreach($projects as $project){
            if ($project->getName() === $name)
                return $project;
        }
        return null;
    }
}