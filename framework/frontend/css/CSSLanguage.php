<?php
namespace regenix\frontend\css;

use regenix\frontend\Language;

class CSSLanguage extends Language {

    public function getName() {
        return 'CSS';
    }

    public function getExtension() {
        return 'css';
    }

    public function getHtmlInsert($path, array $arguments = array()) {
        return '<link rel="stylesheet" href="' . $path . '">';
    }
}