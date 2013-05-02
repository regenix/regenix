<?php

namespace {

    use framework\AbstractBootstrap;
    use ide\DirectoryType;
    use ide\FileType;
    use ide\TextType;
    use ide\Plugin;
    use plugins\core\CorePlugin;

    class Bootstrap extends AbstractBootstrap {

        public function onStart(){
            Plugin::register(new CorePlugin());
        }
    }
}