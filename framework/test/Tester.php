<?php
namespace framework\test;

use framework\Project;
use framework\mvc\Controller;

class Tester extends Controller {

    protected function startTesting(){
        $project = Project::current();
        $path    = $project->getTestPath();
        foreach(glob($path . '*.php') as $file){
            $class = 'tests\\' . substr(basename($file), 0, -4);

            /** @var $test UnitTest */
            $test = new $class;
            $test->startTesting();
        }
    }

    public function run(){
        $this->startTesting();
        $this->renderDump(UnitTest::$tested);
    }

    public function runAsJson(){
        $this->startTesting();
        $all_result = true;
        $json = array('result' => $all_result, 'tests' => array());
        foreach(UnitTest::$tested as $class => $test){
            $class  = str_replace('\\', '.', $class);
            $result = $test->getResult();
            $json['tests'][$class] = array('result' => $test->isOk(), 'log' => $result);
            $all_result = $all_result && $test->isOk();
        }
        $json['result'] = $all_result;
        $this->renderJSON($json);
    }
}