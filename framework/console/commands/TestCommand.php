<?php
namespace regenix\console\commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use regenix\core\Regenix;
use regenix\core\Application;
use regenix\console\RegenixCommand;
use regenix\lang\CoreException;
use regenix\modules\Module;
use regenix\test\Tester;

class TestCommand extends RegenixCommand {

    protected function configure() {
        $this
            ->setName('test')
            ->setDescription('Runs tests of an app or module, for module: test --module=name~0.5')
            ->addOption(
                'module',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $console = $this->getApplication();

        if ($input->getOption('module')){
            $module = $input->getOption('module');
            $module = $module[0];
            Tester::startTesting(null, $module);

            $this->writeln('Start module "%s" testing ...', $module);
        } else {
            $this->checkApplicationLoaded();
            $console->app->register(false);
            $this->writeln('Start "%s" testing ...', $console->app->getName());
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
}