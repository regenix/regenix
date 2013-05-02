<?php
namespace plugins\core;

use ide\FileType;
use ide\Plugin;
use plugins\core\files\CssFile;
use plugins\core\files\DirectoryType;
use plugins\core\files\PhpFile;
use plugins\core\files\TextType;
use plugins\core\projects\MvcType;

class CorePlugin extends Plugin {

    public function __construct(){
        $this->registerProjectType(new MvcType());

        $this->registerFileType(new DirectoryType());
        $this->registerFileType(new TextType());
        $this->registerFileType(new CssFile());
        $this->registerFileType(new PhpFile());
    }

    public function getName() {
        return 'Core';
    }

    protected function getAssets(){
        return array(
            'components.ext.json',
            'js/components.js'
        );
    }
}