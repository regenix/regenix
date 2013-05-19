<?php
namespace framework\console\commands;

use framework\Core;
use framework\Project;
use framework\console\Commander;
use framework\console\ConsoleCommand;
use framework\modules\AbstractModule;
use framework\test\Tester;

class TestCommand extends ConsoleCommand {

    const GROUP = 'test';

    public function __default(){
        $this->project->register(false);

        $this->writeln('Start "%s" testing ...', $this->project->getName());
        $this->writeln();

        Tester::startTesting();
        $result = Tester::getResults();

        foreach($result['tests'] as $name => $test){
            $this->writeln('    - [%s] %s', $test['result'] ? 'ok' : 'fail', substr($name, 6));
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
}