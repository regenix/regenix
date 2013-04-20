<?php
namespace framework\console\commands;

use framework\console\Commander;
use framework\console\ConsoleCommand;

class LoadCommand extends ConsoleCommand {

    const GROUP = 'load';

    public function __default(){
        $name = $this->args->get(0);
        $this->write('Load project: `%s`', $name);

        $cmd = Commander::current();
        if (!$cmd->projects[$name]){
            $this->writeln('[error: not exists]');
        } else {
            putenv('REGENIX_CUR_PROJECT=' . $name);
            $this->writeln('[success]');
        }
    }
}