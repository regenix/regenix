<?php
namespace regenix\console\commands;

use regenix\console\ConsoleCommand;

class InfoCommand extends ConsoleCommand {

    const GROUP = 'info';

    public function __default(){
        $config = $this->app->config;

        $this
            ->writeln('Current: %s', $this->app->getName())
            ->writeln()
            ->writeln('     mode = %s', $config->get('app.mode'))
            ->writeln('     mode.strict = %s', $config->getBoolean('app.mode.strict', true) ? 'on' : 'off');
    }

    public function getInlineHelp(){
        return 'show information of current app';
    }
}