<?php
namespace ide\files;

use ide\EditorType;
use ide\FileType;
use ide\Project;
use ide\TextType;
use ide\editors\CssSourceEditor;

class CssFile extends TextType {

    /** @return string */
    public function getIcon() {
        return 'img/icons/filetypes/css.png';
    }

    /** @return EditorType */
    public function getEditor() {
        return new CssSourceEditor();
    }

    /**
     * @param $project
     * @param $file
     * @return bool
     */
    public function isMatch(Project $project, $file) {
        return pathinfo($file, PATHINFO_EXTENSION) === 'css';
    }
}