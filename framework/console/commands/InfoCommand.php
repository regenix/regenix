<?php
namespace framework\console\commands;

use framework\console\ConsoleCommand;

class InfoCommand extends ConsoleCommand {

    const GROUP = 'info';

    public function __default(){
        $config = $this->project->config;

        $this
            ->writeln('Current: %s', $this->project->getName())
            ->writeln()
            ->writeln('     mode = %s', $config->get('app.mode'))
            ->writeln('     mode.strict = %s', $config->getBoolean('app.mode.strict', true) ? 'on' : 'off');
    }
}