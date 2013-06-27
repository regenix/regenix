<?php
namespace {

    use regenix\AbstractBootstrap;
    use regenix\lang\ClassScanner;
    use regenix\mvc\template\BaseTemplate;
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

        public function onTest(array &$tests){
            $tests = array(
                new ClassloaderTest(),
                new LangTest(),
                new LoggingTest(),
                new I18nTest(),

                new tests\io\FileTest(),

                new tests\config\PropertiesTest(),
                new tests\config\RoutesTest(),

                new tests\mvc\SessionTest(),
                new tests\mvc\FlashTest(),
                new tests\mvc\RequestQueryTest(),
                new tests\mvc\RequestBinderTest(),
                new tests\mvc\URLTest(),

                new tests\mvc\RequestTest()
            );
        }
    }
}