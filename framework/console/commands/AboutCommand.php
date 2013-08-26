<?php
namespace regenix\console\commands;

use regenix\Regenix;
use regenix\Application;
use regenix\console\Commander;
use regenix\console\ConsoleCommand;
use regenix\modules\Module;

class AboutCommand extends ConsoleCommand {

    const GROUP = 'about';

    protected function printModules(){
        $this->writeln('Modules:');
        $this->writeln();

        $modules = Module::getAllModules();
        foreach($modules as $name => $versions){
            $this->writeln('    - %s (%s)', $name, implode(', ', $versions));
        }
   }

    public function __default(){
        $this->writeln('Regenix framework v%s', Regenix::getVersion());
        $this->writeln();
        $this->writeln('    root path: `%s`', ROOT);
        $this->writeln('    apps path: `%s`', Application::getApplicationsPath());
        $this->writeln();

        $this->printModules();

        $cmd = Commander::current();
        $this->writeln();
        $this->writeln('apps:');
        $this->writeln();
        foreach($cmd->apps as $app){
            $this->writeln('    - %s (%s)', $app->getName(), $app->config->get('src.mode'));
        }
    }

    public function getInlineHelp(){
        return 'shows information about versions, apps, modules';
    }
}