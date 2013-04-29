<?php

namespace {

    use framework\AbstractBootstrap;
    use ide\DirectoryType;
    use ide\FileType;
    use ide\TextType;
    use ide\files\CssFile;
    use ide\files\PhpFile;

    class Bootstrap extends AbstractBootstrap {

        public function onStart(){
            FileType::register(new TextType());
            FileType::register(new DirectoryType());

            FileType::register(new CssFile());
            FileType::register(new PhpFile());
        }
    }
}