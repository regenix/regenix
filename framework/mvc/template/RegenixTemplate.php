<?php

namespace framework\mvc\template {

    use framework\Core;
    use framework\libs\RegenixTPL\RegenixTemplate as RegenixTPL;

    class RegenixTemplate extends BaseTemplate {

        const type = __CLASS__;

        const ENGINE_NAME = 'Regenix Template';
        const FILE_EXT = 'html';

        private static $tpl;
        private static $loaded = false;

        public function __construct($templateFile, $templateName){

            if (!self::$loaded){
                self::$tpl = new RegenixTPL();
                self::$tpl->setTempDir( Core::$tempDir . '/regenixtpl/' );
                self::$tpl->setTplDirs( TemplateLoader::getPaths() );
                self::$loaded = true;
            }

            self::$tpl->setFile($templateFile);
        }

        public function render(){
            self::$tpl->render( $this->args, IS_PROD );
        }

        public function registerFunction($name, $callback, $className) {
            // nop
        }
    }
}