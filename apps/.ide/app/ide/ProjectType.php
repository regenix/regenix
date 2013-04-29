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
     * @param $path
     * @return array
     */
    abstract public function getFiles($path);

    /**
     * @param $path
     * @return bool|null
     */
    abstract public function isDirectoryEmpty($path);
}