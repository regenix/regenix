<?php
namespace regenix\console\commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use regenix\Regenix;
use regenix\console\RegenixCommand;
use regenix\lang\File;

class LoadCommand extends RegenixCommand {

    protected function configure() {
        $this
            ->setName('load')
            ->setDescription('Load and set current app with name, example: `load <name>`')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'name of application'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $name = $input->getArgument('name');
        $this->write('Load app: `%s`', $name);

        $console = $this->getApplication();
        if (!$console->apps[$name]){
            $this->writeln('[error: not exists]');
        } else {
            $tmpFile = new File(Regenix::getTempPath() . '/regenix/.current');
            $tmpFile->getParentFile()->mkdirs();
            if (file_put_contents($tmpFile->getPath(), $name))
                $this->writeln('[success]');
            else
                $this->writeln('[error: can`t write to temp directory');
        }
    }
}