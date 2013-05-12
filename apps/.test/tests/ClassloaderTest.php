<?php
namespace tests;

use framework\Project;
use framework\lang\ClassLoader;

class ClassloaderTest extends BaseTest {

    const type = __CLASS__;

    public function system(){
        $this->req(ClassLoader::$modulesLoader);
        $loader = ClassLoader::$frameworkLoader;
        $this->req($loader);
        if ($loader){
            $this->req($loader->findFile('framework\\exceptions\\HttpException'));
        }
    }

    public function project(){
        $loader = Project::current()->classLoader;
        $this->req($loader);
        if ($loader){
            $this->req($loader->findFile('\\Bootstrap'));
            $this->req($loader->findFile('Bootstrap'));
            $this->req($loader->findFile('controllers\\Application'));
            $this->notReq($loader->findFile('controllers\\Application.php'));
        }
    }
}