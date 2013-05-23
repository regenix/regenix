<?php
namespace tests;

use framework\Core;
use framework\Project;
use framework\lang\ClassLoader;
use framework\lang\ClassScanner;

class ClassloaderTest extends RegenixTest {

    const type = __CLASS__;

    public function system(){
        $loader = ClassLoader::$frameworkLoader;
        $this->assertRequire($loader);
        if ($loader){
            $this->assertRequire($loader->findFile('framework\\exceptions\\HttpException'));
        }
    }

    public function project(){
        $loader = Core::$classLoader;
        $scanner = ClassScanner::current();

        $this->assertRequire($loader);
        $this->assertRequire($scanner);
        if ($loader){
            $this->assertRequire(ClassScanner::find('\\Bootstrap'));
            $this->assertRequire(ClassScanner::find('Bootstrap'));
            $this->assertRequire(ClassScanner::find('controllers\\Application'));
            $this->assertNotRequire(ClassScanner::find('controllers\\Application.php'));
        }
    }
}