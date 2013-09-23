<?php
namespace regenix\console\commands;


use regenix\Regenix;
use regenix\console\ConsoleCommand;
use regenix\lang\CoreException;

class PropelCommand extends ConsoleCommand {

    const GROUP = 'propel';

    public function __default(){
        if (!$this->app)
            throw new CoreException("To work with the command, load some application via `regenix load <app_name>`");

        $propelBin = ROOT . 'propel-gen';
        $path = $this->app->getPath() . 'conf/orm/';

        $output = '';
        $command = $propelBin . ' "' . $path . '" ' . $this->args->get(0) .
            ' "-Dpropel.name=" "-Dpropel.php.dir=' . $this->app->getModelPath() . '"' .
            ' "-Dpropel.schema.autoNamespace=true"';

        $this->writeln('>> ' . $command);
        $this->writeln();
        exec($command, $output);

        foreach((array)$output as $line)
        foreach((array)$output as $line)
            $this->writeln($line);
    }

    public function getInlineHelp()
    {
        return 'propel-gen command within a current loaded application';
    }
}