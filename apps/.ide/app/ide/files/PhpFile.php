<?php
namespace ide\files;

use ide\EditorType;
use ide\FileType;
use ide\Project;
use ide\TextType;
use ide\editors\SourceEditor;

class PhpFile extends TextType {

    /** @return string */
    public function getIcon() {
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