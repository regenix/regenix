<?php

namespace regenix\mvc\template {

    class PHPTemplate extends BaseTemplate {

        const type = __CLASS__;

        const ENGINE_NAME = 'PHP Template';
        const FILE_EXT = 'phtml';

        public function render(){

            extract($this->args, EXTR_PREFIX_INVALID | EXTR_OVERWRITE, 'arg_');
            include $this->file;
        }
    }
}