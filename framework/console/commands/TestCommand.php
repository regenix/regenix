<?php
namespace regenix\console\commands;

use regenix\Regenix;
use regenix\Application;
use regenix\console\Commander;
use regenix\console\ConsoleCommand;
use regenix\modules\Module;
use regenix\test\Tester;

class TestCommand extends ConsoleCommand {

    const GROUP = 'test';

    public function __default(){

        if ($this->opts->has('module')){
            $module = $this->opts->get('module');
            Tester::startTesting(null, $module);

            $this->writeln('Start module "%s" testing ...', $module);
        } else {
            $this->app->register(false);
            $this->writeln('Start "%s" testing ...', $this->app->getName());
            Tester::startTesting();
        }
        $this->writeln();

        $result = Tester::getResults();
        foreach($result['tests'] as $name => $test){
            $shortName = substr($name, strpos($name, 'tests.') + 6);

            $this->writeln('    - [%s] %s', $test['result'] ? 'ok' : 'fail', $shortName);
            if (!$test['result']){
                foreach($test['log'] as $method => $logs){
                    foreach($logs as $log){
                        if (!$log['result']){
                            $this->writeln('        * %s, %s, line %s (%s)', $method,
                                $log['method'], $log['line'], $log['message'] ? $log['message'] : '...');
                        }
                    }
                }
                $this->writeln();
            }
        }

        $this->writeln();
        $this->writeln('Tests %s, exit code: %s', $result['result'] ? 'success' : 'fail', $result['result'] ? 0 : 1);
        exit($result['result'] ? 0 : 1);
    }

    public function getInlineHelp(){
        return 'run tests of app or module, for module: test -module=name~0.5';
    }
}