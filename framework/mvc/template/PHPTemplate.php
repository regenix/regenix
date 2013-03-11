<?php

namespace framework\mvc\template {

    class PHPTemplate extends BaseTemplate {

        const ENGINE_NAME = 'PHP Template';
        const FILE_EXT = 'phtml';

        public function render(){

            extract($this->args, EXTR_PREFIX_INVALID | EXTR_OVERWRITE, 'arg_');
            include $this->file;
        }

        public function registerFunction($name, $callback, $className) {
            \TPL::__addFunction($name, $callback);
               // nop
        }
    }
}

namespace {
    
    class TPL {
        
        private static $funcs = array();

        public static function __addFunction($name, $callback){
            self::$funcs[ $name ] = $callback;
        }
        
        public static function __callStatic($name, $arguments) {
            
            $callback = self::$funcs[ $name ];
            return call_user_func_array($callback, $arguments);
        }
    }
}
