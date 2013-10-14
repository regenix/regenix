<?php
namespace regenix\frontend\javascript;

use regenix\frontend\Language;

class JavascriptLanguage extends Language {

    public function getName() {
        return 'JavaScript';
    }

    public function getExtension() {
        return 'js';
    }

    public function getHtmlInsert($path, array $arguments = array()) {
        return '<script type="text/javascript" src="' . $path . '"></script>';
    }
}