<?php
namespace ide;

use ide;

final class Project {

    /** @var ProjectType */
    public $type;

    /** @var string */
    protected $name;

    public function __construct($name, ProjectType $type){
        $this->type = $type;
        $this->name = $name;
        $type->setProject($this);
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
     * @return array
     */
    public static function findAll($cached = true){
        if ($cached && self::$projects)
            return self::$projects;

        self::$projects = array();

        $srcDir = \framework\Project::getSrcDir();
        $dirs   = scandir($srcDir);
        foreach($dirs as $dir){
            if ($dir == '..' || $dir == '.' || $dir == '.ide')
                continue;

            if (is_dir($dir)){
                self::$projects[] = new Project($dir, new MvcType());
            }
        }
        return self::$projects;
    }
}