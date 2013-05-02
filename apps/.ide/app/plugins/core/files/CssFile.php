<?php
namespace plugins\core\files;

use ide\EditorType;
use ide\FileType;
use ide\Project;
use plugins\core\editors\CssSourceEditor;

class CssFile extends TextType {

    /** @return string */
    protected function getIcon() {
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