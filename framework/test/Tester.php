<?php
namespace regenix\test;

use regenix\Application;
use regenix\Regenix;
use regenix\cache\SystemCache;
use regenix\lang\CoreException;
use regenix\lang\ClassScanner;
use regenix\mvc\Controller;

class Tester extends Controller {

    public static function startTesting($id = null, $moduleWithVersion = null){
        $app =  Regenix::app();

        if ($moduleWithVersion){
            if (!is_dir(ROOT . 'modules/' . $moduleWithVersion . '/'))
                throw new CoreException('Module `%s` not found', $moduleWithVersion);

            SystemCache::removeAll();
            ClassScanner::addClassRelativePath('modules/' . $moduleWithVersion);

            $module = explode('~', $moduleWithVersion, 2);
            $namespace = 'modules\\' . $module[0] . '\\';
        } else {
            $module = null;
            ClassScanner::addClassPath(Regenix::app()->getTestPath());
            $namespace = 'tests\\';
        }

        $tests = array();
        if ($id){
            $testClass = ClassScanner::find(str_replace('.', '\\', $id));
            if (!$testClass->isChildOf( UnitTest::type ))
                throw new CoreException('Class "%s" should be inherited by "%s"',
                    $testClass->getName(), UnitTest::type);

            $tests[] = new $testClass;
        } else {
            $testClass = ClassScanner::find(UnitTest::type);
            foreach($testClass->getChildrensAll($namespace) as $child){
                $class = $child->getName();
                $reflection = new \ReflectionClass($class);
                if ($reflection->isAbstract())
                    continue;

                $test    = new $class;
                $tests[] = $test;
            }

            if (!$module && $app && $app->bootstrap)
                $app->bootstrap->onTest($tests);
        }

        foreach($tests as $test){
            /** @var $test UnitTest */
            $test->startTesting();
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
        $this->put('app', Regenix::app());

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