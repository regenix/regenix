<?php
namespace regenix\console\commands;

use regenix\Regenix;
use regenix\Application;
use regenix\cache\SystemCache;
use regenix\console\Commander;
use regenix\console\ConsoleCommand;
use regenix\modules\Module;

class CleanCommand extends ConsoleCommand {

    const GROUP = 'clean';

    public function __default(){
        SystemCache::removeAll();
        $this->writeln('[success] Cache has been removed.');
    }

    public function getInlineHelp(){
        return 'clean all cache\'s data';
    }
}