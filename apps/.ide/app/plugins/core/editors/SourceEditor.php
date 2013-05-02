<?php
namespace plugins\core\editors;

use ide\EditorType;

class SourceEditor extends EditorType {

    protected function getAssets(){
        $result = array(
            'codemirror/lib/codemirror.css',
            'codemirror/lib/codemirror.js',
            'js/source.editor.js'
        );

        return $result;
    }
}