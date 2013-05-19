<?php
namespace framework\test;

use framework\Project;
use framework\exceptions\CoreException;
use framework\lang\ClassLoader;
use framework\mvc\Controller;

class Tester extends Controller {

    public static function startTesting($id = null, $moduleWithVersion = null){
        $project = Project::current();

        $prefix = '';
        if ($moduleWithVersion){
            if (!is_dir(ROOT . 'modules/' . $moduleWithVersion . '/'))
                throw CoreException::formated('Module `%s` not found', $moduleWithVersion);

            $path   = ROOT . 'modules/' . $moduleWithVersion . '/tests/';

            $module = explode('~', $moduleWithVersion, 2);
            ClassLoader::$modulesLoader->addModule($module[0], $module[1]);
            $prefix = 'modules\\' . $module[0] . '\\';
        } else
            $path = $project->getTestPath();

        $tests = array();

        if (is_dir($path)){
            $it = new \RecursiveDirectoryIterator($path);
            foreach(new \RecursiveIteratorIterator($it) as $file){
                /** @var \SplFileInfo $file */
                if ($file->isFile() && $file->getExtension() === 'php'){
                    $filename = str_replace(array('\\', $path), array('/', ''), $file->getRealPath());
                    $class = $prefix . 'tests\\' . str_replace('/', '\\', substr($filename, 0, -4));

                    if ($id){
                        if (str_replace('\\', '.', $class) !== $id)
                            continue;
                    }

                    $reflection = new \ReflectionClass($class);
                    if ($reflection->isAbstract())
                        continue;

                    /** @var $test UnitTest */
                    $test    = new $class;
                    $tests[] = $test;
                }
            }

            if ($project->bootstrap)
                $project->bootstrap->onTest($tests);

            foreach($tests as $test){
                $test->startTesting();
            }
        }
    }

    public static function getResults(){
        $all_result = true;
        $json = array('result' => $all_result, 'tests' => array());
        foreach(UnitTest::$tested as $class => &$test){
            $class  = str_replace('\\', '.', $class);
            if ($test == null){
                $json['tests'][$class] = array(
                    'result' => false,
                    'class'  => $class,
                    'skip'   => true
                );
                continue;
            }

            $result = $test->getResult();
            $fails  = array();
            foreach($result as $method => &$list){
                foreach($list as $call){
                    if (!$call['result']){
                        $fails[] = $method;
                        break;
                    }
                }
            }

            $json['tests'][$class] = array(
                'result' => $test->isOk(),
                'class' => $class,
                'fails' => $fails,
                'log' => $result);
            $all_result = $all_result && $test->isOk();
        }
        $json['result'] = $all_result;
        return $json;
    }

    protected static function getSourceLine($file, $line, $offset = 1){
        if (file_exists($file) && is_readable($file) ){
            $fp = fopen($file, 'r');
            $n  = 1;
            $source = array();
            while($str = fgets($fp, 4096)){
                if ( $n > $line - $offset && $n < $line + $offset ){
                    $source[$n] = $str;
                }
                if ( $n > $line + $offset )
                    break;
                $n++;
            }
            fclose($fp);
            return $source;
        }
        return null;
    }

    protected function detail($detail){
        $this->notFoundIfEmpty($detail);
        foreach($detail['log'] as $method => &$calls){
            foreach($calls as &$call){
                $file = $call['file'];
                $call['source'] = static::getSourceLine($call['file'], $call['line']);
            }
            unset($call);
        }
        $detail['file'] = str_replace(ROOT, '/', str_replace(DIRECTORY_SEPARATOR, '/', $file));
        $this->put('detail', $detail);
    }

    public function run($id = null){
        self::startTesting($id);
        $result = static::getResults();
        $this->put('project', Project::current());

        if ($id){
            $this->detail($detail = $result['tests'][$id]);
            $this->response->setHeader('Test-Status', $detail['result'] ? 'success' : 'fail');
        } else {
            $this->response->setHeader('Test-Status', $result['result'] ? 'success' : 'fail');
            $this->put('result', $result);
        }
        $this->render();
    }

    public function runAsJson(){
        self::startTesting();
        $this->renderJSON(static::getResults());
    }
}