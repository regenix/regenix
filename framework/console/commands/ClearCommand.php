<?php
namespace regenix\console\commands;

use regenix\cache\SystemCache;
use regenix\console\Commander;
use regenix\console\ConsoleCommand;
use regenix\lang\File;

class ClearCommand extends ConsoleCommand {

    const GROUP = 'clear';

    public function __default(){
        SystemCache::removeAll();
        $this->writeln('Cache is cleared...');
    }

    public function getInlineHelp(){
        return 'clears all caches';
    }
}