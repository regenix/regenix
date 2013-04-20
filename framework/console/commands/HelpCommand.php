<?php
namespace framework\console\commands;

use framework\console\ConsoleCommand;

class HelpCommand extends ConsoleCommand {

    const GROUP = 'help';

    public function __default(){
        $this->writeln('All commands:');
        $this->writeln();
        $this->writeln('    help - show help');
        $this->writeln('    about - show version, projects, modules information');
        $this->writeln('    info - show information of current project');
        $this->writeln();
        $this->writeln('    load <name> - load and set current project by name');
    }
}