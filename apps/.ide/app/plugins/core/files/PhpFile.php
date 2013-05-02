<?php
namespace plugins\core\files;

use ide\EditorType;
use ide\FileType;
use ide\Project;
use plugins\core\editors\SourceEditor;

class PhpFile extends TextType {

    /** @return string */
    protected function getIcon() {
        return 'img/icons/filetypes/php.png';
    }

    /** @return EditorType */
    public function getEditor() {
        return new SourceEditor();
    }

    /**
     * @param $project
     * @param $file
     * @return bool
     */
    public function isMatch(Project $project, $file) {
        return pathinfo($file, PATHINFO_EXTENSION) === 'php';
    }
}