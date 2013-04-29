<?php
namespace ide\editors;

use ide\EditorType;

class SourceEditor extends EditorType {

    public function getAssets(){
        $result = array(
            'codemirror/lib/codemirror.css',
            'codemirror/lib/codemirror.js',
            'js/source.editor.js'
        );

        return $result;
    }
}