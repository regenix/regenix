<?php
namespace regenix\console\commands;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use regenix\cache\Cache;
use regenix\console\ConsoleCommand;
use regenix\console\RegenixCommand;
use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\scheduler\Scheduler;

class SchedulerCommand extends RegenixCommand {

    const CHECK_APP_LOADED = true;

    protected function configure() {
        $this
            ->setName('scheduler')
            ->setDescription('Start scheduler')
            ->addOption(
                'daemon',
                null,
                InputOption::VALUE_NONE
            )
            ->addOption(
                'interval',
                 null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Set interval in seconds for scheduler loop, default 2 sec'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $console = $this->getApplication();

        $interval = $input->getOption('interval');
        $interval = (int)$interval[0];
        if (!$interval)
            $interval = 2;

        $pid = getmygid();
        $name = $console->app->getName();

        $this->writeln('Scheduler (%s) is started with update interval = ' . $interval . 's', $name);
        $scheduler = new Scheduler($console->app->getName());
        $tasks = $scheduler->getTasks();
        $this->writeln('    Task count: %s', sizeof($tasks));

        if ($input->getOption('daemon')){
            $process = new Process('regenix scheduler');
            $process->setTimeout(0);
            $process->start();

            $pid = $process->getPid();

            $this->writeln('    Scheduler PID: %s', $pid);
            $this->writeln();
            $this->writeln('  (!) Scheduler is working in background, to stop it use `regenix scheduler stop`');
        } else {
            $this->writeln('    Scheduler PID: ' . getmygid());
        }

        $file = new File($console->app->getLogPath() . '/scheduler.pid');
        if (!$file->exists())
            $file->getParentFile()->mkdirs();
        else {

        }

        $file->open('w');
        $file->write($pid);
        $file->close();
        $this->writeln();

        if (sizeof($tasks) === 0){
            $file->delete();
            throw new CoreException('Can`t run scheduler with empty tasks');
        } else {
            if (!$input->getOption('daemon')){
                while(true){
                    $scheduler->update();
                    time_nanosleep($interval, 0);
                }
                $file->delete();
            }
        }
    }
}