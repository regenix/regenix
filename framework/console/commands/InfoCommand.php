<?php
namespace regenix\console\commands;

use regenix\console\ConsoleCommand;
use regenix\lang\CoreException;

class InfoCommand extends ConsoleCommand {

    const GROUP = 'info';

    public function __default(){
        if (!$this->app)
            throw new CoreException("To work with the command, load some application via `regenix load <app_name>`");

        $config = $this->app->config;

        $this
            ->writeln('Current: %s', $this->app->getName())
            ->writeln()
            ->writeln('     mode = %s', $config->get('app.mode'))
            ->writeln('     mode.strict = %s', $config->getBoolean('app.mode.strict', true) ? 'on' : 'off');
    }

    public function getInlineHelp(){
        return 'shows information of current app';
    }
}