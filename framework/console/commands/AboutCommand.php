<?php
namespace regenix\console\commands;

use regenix\Core;
use regenix\Project;
use regenix\console\Commander;
use regenix\console\ConsoleCommand;
use regenix\modules\AbstractModule;

class AboutCommand extends ConsoleCommand {

    const GROUP = 'about';

    protected function printModules(){
        $this->writeln('Modules:');
        $this->writeln();

        $modules = AbstractModule::getAllModules();
        foreach($modules as $name => $versions){
            $this->writeln('    - %s (%s)', $name, implode(', ', $versions));
        }
   }

    public function __default(){
        $this->writeln('Regenix framework v%s', Core::getVersion());
        $this->writeln();
        $this->writeln('    root path: `%s`', ROOT);
        $this->writeln('    apps path: `%s`', Project::getSrcDir());
        $this->writeln();

        $this->printModules();

        $cmd = Commander::current();
        $this->writeln();
        $this->writeln('Projects:');
        $this->writeln();
        foreach($cmd->projects as $project){
            $this->writeln('    - %s (%s)', $project->getName(), $project->config->get('app.mode'));
        }
    }

    public function getInlineHelp(){
        return 'show information about versions, projects, modules';
    }
}