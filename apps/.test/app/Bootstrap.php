<?php
namespace {

    use framework\AbstractBootstrap;
    use framework\mvc\template\BaseTemplate;
    use tests\ClassloaderTest;
    use tests\I18nTest;
    use tests\LangTest;
    use tests\LoggingTest;

    class Bootstrap extends AbstractBootstrap {

        public function onStart(){

        }

        public function onFinish(){

        }

        public function onUseTemplates(){

        }

        public function onTest(&$tests){
            $tests = array(
                new ClassloaderTest(),
                new LangTest(),
                new LoggingTest(),
                new I18nTest(),

                new tests\io\FileTest(),

                new tests\config\PropertiesTest(),
                new tests\config\RoutesTest(),

                new tests\mvc\SessionTest(),
                new tests\mvc\FlashTest()
            );
        }
    }
}