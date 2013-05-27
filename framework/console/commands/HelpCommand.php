<?php
namespace regenix\console\commands;

use regenix\console\Commander;
use regenix\console\ConsoleCommand;

class HelpCommand extends ConsoleCommand {

    const GROUP = 'help';

    public function __default(){
        $this->writeln('All commands <regenix [command] ..args>:');
        $this->writeln();

        $commands = Commander::getCommands();

        foreach($commands as $name => $command){
            $this->writeln('    `' . $name . '` - ' . $command->getInlineHelp());
        }
        $this->writeln();
    }

    public function getInlineHelp(){
        return 'show help';
    }
}