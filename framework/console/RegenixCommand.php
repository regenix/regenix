<?php
namespace regenix\console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use regenix\lang\CoreException;
use regenix\lang\String;

/**
 * Class RegenixCommand
 * @package regenix\console
 * @method \regenix\console\RegenixConsole getApplication()
 */
abstract class RegenixCommand extends Command {
    const type = __CLASS__;

    const CHECK_APP_LOADED = false;

    /** @var InputInterface */
    protected $input;

    /** @var OutputInterface */
    protected $output;

    protected function initialize(InputInterface $input, OutputInterface $output){
        $this->input = $input;
        $this->output = $output;
        $this->writeln();
        if (static::CHECK_APP_LOADED)
            $this->checkApplicationLoaded();
    }

    protected function checkApplicationLoaded(){
        $console = $this->getApplication();
        if (!$console->app)
            throw new CoreException(
                "To work with the command, load some application via `regenix load <app_name>`"
            );
    }

    protected function writeln($message = ''){
        $this->output->writeln('    ' . String::formatArgs($message, array_slice(func_get_args(), 1)));
        return $this;
    }

    protected function write($message){
        $this->output->write('    ' . String::formatArgs($message, array_slice(func_get_args(), 1)));
        return $this;
    }
}
