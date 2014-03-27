<?php
namespace {

    use regenix\core\AbstractBootstrap;
    use regenix\mvc\template\BaseTemplate;

    class Bootstrap extends AbstractBootstrap {
        public function onStart(){
            // warning: do not use include() and require()
        }

        public function onException(\Exception $e) {
            // do something when exception ... * globally
        }

        public function onTest(array &$tests) {
            // redefine the list of your test classes if needed
            // by default $tests contains the all names of test classes
        }

        public function onTemplateRender(BaseTemplate $template) {
            // here you can put a global var for your all templates or something else
            // $template->put("global_var", "value");
        }
    }
}