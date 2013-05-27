<?php
namespace framework\console\commands;

use framework\console\Commander;
use framework\console\ConsoleCommand;
use framework\lang\File;

class LoadCommand extends ConsoleCommand {

    const GROUP = 'load';

    public function __default(){
        $name = $this->args->get(0);
        $this->write('Load project: `%s`', $name);

        $cmd = Commander::current();
        if (!$cmd->projects[$name]){
            $this->writeln('[error: not exists]');
        } else {
            $tmpFile = new File(sys_get_temp_dir() . '/regenix/.current');
            $tmpFile->getParentFile()->mkdirs();
            if (file_put_contents($tmpFile->getPath(), $name))
                $this->writeln('[success]');
            else
                $this->writeln('[error: can`t write to temp directory');
        }
    }
}