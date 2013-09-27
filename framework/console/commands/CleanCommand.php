<?php
namespace regenix\console\commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use regenix\Regenix;
use regenix\Application;
use regenix\lang\SystemCache;
use regenix\console\Commander;
use regenix\console\RegenixCommand;
use regenix\modules\Module;

class CleanCommand extends RegenixCommand {

    protected function configure() {
        $this
            ->setName('clean')
            ->setDescription('Clean all cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        SystemCache::removeAll();
        $this->writeln('[success] Cache has been removed.');
    }
}