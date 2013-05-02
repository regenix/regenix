<?php
namespace ide;

abstract class ProjectType {

    /** @var Project */
    protected $project;

    public function __construct(Project $project = null){
        $this->setProject($project);
    }

    public function setProject(Project $project){
        $this->project = $project;
    }

    /**
     * @return Plugin
     */
    public function getPlugin(){
        return Plugin::getByInstance($this);
    }

    /** @return string */
    abstract protected function getIcon();

    /**
     * @return string
     */
    public function getRealIcon(){
        $icon = $this->getIcon();
        $plugin = $this->getPlugin();
        if ($plugin)
            return $plugin->getAssetRealPath($icon);
        else
            return $icon;
    }

    /** @return array */
    protected function getAssets(){
        return array();
    }

    /**
     * @return array
     */
    public function getRealAssets(){
        /** @var $plugin Plugin */
        return ($plugin = $this->getPlugin())
            ? $plugin->getAssetRealPaths($this->getAssets()) : $this->getAssets();
    }

    /**
     * @param $project
     * @return bool
     */
    abstract public function isMatch(Project $project);

    /**
     * @param $path
     * @return array
     */
    abstract public function getFiles($path);

    /**
     * @param $path
     * @return bool|null
     */
    abstract public function isDirectoryEmpty($path);


    /** @var ProjectType[] */
    private static $projectTypes = array();

    public static function register(ProjectType $type){
        self::$projectTypes[] = $type;
    }

    public static function getProjectType(Project $project){
        foreach(self::$projectTypes as $type){
            if ($type->isMatch($project)){
                $result = clone $type;
                $result->setProject($project);
                return $result;
            }
        }
        return null;
    }
}