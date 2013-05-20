<?php
namespace tests;

use framework\Project;
use framework\lang\ClassLoader;

class ClassloaderTest extends BaseTest {

    const type = __CLASS__;

    public function system(){
        $this->assertRequire(ClassLoader::$modulesLoader);
        $loader = ClassLoader::$frameworkLoader;
        $this->assertRequire($loader);
        if ($loader){
            $this->assertRequire($loader->findFile('framework\\exceptions\\HttpException'));
        }
    }

    public function project(){
        $loader = Project::current()->classLoader;
        $this->assertRequire($loader);
        if ($loader){
            $this->assertRequire($loader->findFile('\\Bootstrap'));
            $this->assertRequire($loader->findFile('Bootstrap'));
            $this->assertRequire($loader->findFile('controllers\\Application'));
            $this->assertNotRequire($loader->findFile('controllers\\Application.php'));
        }
    }
}