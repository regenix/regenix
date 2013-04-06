<?php
namespace framework\test;

use framework\Project;
use framework\mvc\Controller;

class Tester extends Controller {

    public function run(){

        $project = Project::current();
        $path    = $project->getTestPath();
        foreach(glob($path . '*.php') as $file){
            $class = 'tests\\' . substr(basename($file), 0, -4);

            /** @var $test UnitTest */
            $test = new $class;
            $test->startTesting();
        }

        $this->renderDump(UnitTest::$tested);
    }
}