<?php

namespace framework\mvc\template {
    
    use framework\mvc\template\extension\StandartTemplateFunctions;

    abstract class BaseTemplate {

        const TWIG = 'Twig';
        const SMARTY = 'Smarty';
        const PHP = 'PHP';

        protected $file;
        protected $name;
        protected $args = array();

        const ENGINE_NAME = 'abstract';
        const FILE_EXT    = '???';
        
        private static $initTemplate = false;

        public function __construct($templateFile, $templateName) {
            
            $this->file = $templateFile;
            $this->name = $templateName;
        }
        
        /**
         * @param TemplateFunctions $funcs
         */
        abstract function registerFunction($name, $callback, $className);

        public function onBeforeRender(){
            
            foreach(TemplateLoader::$FUNCTIONS as $funcsClass){
                
                $reflect = new \ReflectionClass($funcsClass);
                
                foreach($reflect->getMethods() as $method){
                    /** @var \ReflectionMethod $method */
                    if ($method->isPublic() && $method->isFinal() && $method->isStatic()){
                        $methodName = $method->getName();
                        $this->registerFunction($methodName, $funcsClass . '::' . $methodName, $funcsClass);
                    }
                }
            }
        }

        public function getContent(){ return null; } 
        public function render(){}


        public function putArgs(array $args = array()){
            $this->args = $args;
        }
    }

}