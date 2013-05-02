<?php
namespace plugins\core\files;

use ide\EditorType;
use ide\FileType;
use ide\Project;
use plugins\core\editors\SourceEditor;

class TextType extends FileType {

    /** @return string */
    protected function getIcon() {
        return 'img/icons/filetypes/text.png';
    }

    /** @return EditorType */
    public function getEditor() {
        return new SourceEditor();
    }

    public function isMatch(Project $project, $file){
        return true;
    }

    public function save(Project $project, $file, $data){
        return (bool)file_put_contents($project->getPath() . $file, $data);
    }

    /**
     * @param Project $project
     * @param $file
     * @return mixed
     */
    public function remove(Project $project, $file) {
        if (file_exists($file = $project->getPath() . $file)){
            return @unlink($file);
        }
        return false;
    }
}