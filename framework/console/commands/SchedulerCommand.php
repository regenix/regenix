<?php
namespace regenix\console\commands;


use regenix\cache\Cache;
use regenix\console\ConsoleCommand;
use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\scheduler\Scheduler;

class SchedulerCommand extends ConsoleCommand {

    const GROUP = 'scheduler';

    public function __default(){
        if (!$this->app)
            throw new CoreException("To work with the command, load some application via `regenix load <app_name>`");

        $interval = $this->opts->getInteger('interval', 2);

        $scheduler = new Scheduler($this->app->getName());
        $tasks = $scheduler->getTasks();

        $this->writeln('Scheduler is started with update interval = ' . $interval . 's');
        $this->writeln('    Task count: ' . sizeof($tasks));
        $this->writeln('    Scheduler PID: ' . getmypid());

        $file = new File($this->app->getLogPath() . '/scheduler.pid');
        if (!$file->exists())
            $file->getParentFile()->mkdirs();

        $file->open('w+');
        $file->write(getmygid());
        $file->close();

        $this->writeln();

        if (sizeof($tasks) === 0){
            throw new CoreException('Can`t run scheduler with empty tasks');
        } else {
            while(true){
                $scheduler->update();
                sleep($interval);
            }
        }

        $file->delete();
    }

    public function getInlineHelp(){
        return 'starts scheduler';
    }
}