<?php
namespace regenix\console\commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use regenix\core\Regenix;
use regenix\core\Application;
use regenix\console\RegenixCommand;
use regenix\modules\Module;

class AboutCommand extends RegenixCommand {

    protected function configure() {
        $this
            ->setName('about')
            ->setDescription('Shows information about versions, apps, modules');
    }

    protected function printModules(){
        $this->writeln('Modules:');
        $this->writeln();

        $modules = Module::getAllModules();
        foreach($modules as $name => $versions){
            $this->writeln('    - %s (%s)', $name, implode(', ', $versions));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $this->writeln('Regenix framework v%s', Regenix::getVersion());
        $this->writeln();
        $this->writeln('    root path: `%s`', ROOT);
        $this->writeln('    apps path: `%s`', Application::getApplicationsPath());
        $this->writeln('    temp path: `%s`', Regenix::getTempPath());
        $this->writeln();

        $this->printModules();

        $console = $this->getApplication();

        $this->writeln();
        $this->writeln('apps:');
        $this->writeln();
        foreach($console->apps as $app){
            $this->writeln('    - %s (%s)', $app->getName(), $app->config->get('app.mode'));
        }
    }
}