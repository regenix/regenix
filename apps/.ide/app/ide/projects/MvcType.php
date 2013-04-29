<?php
namespace ide\projects;

use ide\FileType;
use ide\Project;
use ide\ProjectType;

class MvcType extends ProjectType {

    protected $root;

    public function setProject(Project $project){
        $this->root = $project ? str_replace(DIRECTORY_SEPARATOR, '/', $project->getPath()) : null;
    }

    /**
     * @param $path
     * @return bool
     */
    public function isDirectoryEmpty($path) {
        if (!is_readable($this->root . $path)) return null;

        $handle = opendir($this->root . $path);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                return false;
            }
        }
        return true;
    }

    public function getFiles($path) {
        $result = array();
        foreach (glob($this->root . $path . '/*.*', GLOB_MARK) as $filename) {
            if ($filename == '..' || $filename == '.') continue;

            $type = FileType::getFileType($this->project, $filename);
            $result[] = array(
                'name' => basename($filename),
                'is_dir' => is_dir($filename),
                'type' => $type->toJson()
            );
        }
        return $result;
    }
}