<?php
namespace regenix\console\commands;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use regenix\Regenix;
use regenix\console\ConsoleCommand;
use regenix\console\RegenixCommand;
use regenix\lang\CoreException;
use regenix\lang\File;

class PropelCommand extends RegenixCommand {

    const CHECK_APP_LOADED = true;

    protected function configure() {
        $this
            ->setName('propel')
            ->setDescription('Propel-gen command within a current loaded application`')
            ->addArgument(
                'task',
                InputArgument::OPTIONAL,
                'additional propel command'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $console = $this->getApplication();
        $propelBin = ROOT . 'propel-gen';
        $path = $console->app->getPath() . 'conf/orm/';

        $schemaFile = new File($path . 'schema.xml');
        $propertiesFile = new File($path . 'build.properties');
        $runtimeConfig = new File($path . 'runtime-conf.xml');
        $buildtimeConfig = new File($path . 'buildtime-conf.xml');

        if (!$schemaFile->isFile()){
            $this->writeln(
                '[error] Cannot find a schema file `/apps/%s/conf/orm/schema.xml`',
                $console->app->getName()
            );
            exit(1);
        }

        if (!$propertiesFile->isFile()){
            $this->writeln(
                '[error] Cannot find a build file `/apps/%s/conf/orm/build.properties`',
                $console->app->getName()
            );
            exit(1);
        }

        if (!$runtimeConfig->exists()){
            $this->writeln(
                '[error] Cannot find a runtime-conf file `/apps/%s/conf/orm/runtime-conf.xml`',
                $console->app->getName()
            );
            exit(1);
        }

        if (!$buildtimeConfig->exists()){
            @copy($runtimeConfig->getPath(), $buildtimeConfig->getPath());
        }

        $command = $propelBin . ' "' . $path . '" ' . $input->getArgument('task') .
            ' "-Dpropel.name=" "-Dpropel.php.dir=' . $console->app->getModelPath() . '"' .
            ' "-Dpropel.schema.autoNamespace=true"';

        $this->writeln('>> ' . $command);
        $this->writeln();

        $process = new Process($command);
        $result = $process->run(function($type, $out) {
            if ($type === 'err')
                $this->writeln('ERROR > ' . $out);
            else
                $this->writeln($out);
        });

        if ($result > 0){
            $this->writeln('[fail] Propel return %s exit code', $result);
            exit($result);
        }
    }
}