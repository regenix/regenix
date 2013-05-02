<?php
namespace plugins\core\editors;


class CssSourceEditor extends SourceEditor {

    protected function getAssets(){
        $result = parent::getAssets();
        $result[] = 'codemirror/mode/css/css.js';
        return $result;
    }
}