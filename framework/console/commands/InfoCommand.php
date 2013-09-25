<?php
namespace regenix\console\commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use regenix\console\ConsoleCommand;
use regenix\console\RegenixCommand;
use regenix\lang\CoreException;

class InfoCommand extends RegenixCommand {

    const CHECK_APP_LOADED = true;

    protected function configure() {
        $this
            ->setName('info')
            ->setDescription('Show information about current loaded application');
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $console = $this->getApplication();
        $config = $console->app->config;

        $strict = $config->getBoolean('app.mode.strict', true);
        $this
            ->writeln('Current: %s', $console->app->getName())
            ->writeln()
            ->writeln('     rules = %s', $config->get('app.rules'))
            ->writeln('     mode = %s %s', $config->get('app.mode'), $strict ? '(strict)' : '');
    }
}