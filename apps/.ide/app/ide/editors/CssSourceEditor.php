<?php
namespace ide\editors;


class CssSourceEditor extends SourceEditor {

    public function getAssets(){
        $result = parent::getAssets();
        $result[] = 'codemirror/mode/css/css.js';
        return $result;
    }
}