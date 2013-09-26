<?php
namespace regenix\console\commands;

use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use regenix\console\Commander;
use regenix\console\Console;
use regenix\Application;
use regenix\console\RegenixCommand;
use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\lang\String;

class NewCommand extends RegenixCommand {

    protected function configure() {
        $this
            ->setName('new')
            ->setDescription('Create a new application: `regenix new <name>`')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'name of a new application'
            );
    }

    private static function recursive_copy($src, $dst, $replaces = array()) {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    self::recursive_copy($src . '/' . $file,$dst . '/' . $file, $replaces);
                } else {
                    copy($src . '/' . $file, $copyFile = $dst . '/' . $file);
                    if ($replaces){
                        $tmp = new File($copyFile);
                        $ext = $tmp->getExtension();

                        if ($ext === 'conf' || $ext === 'json' || $ext === 'properties' ||
                            $ext === 'xml' || $ext === 'route' || $ext === 'lang' || $ext === ''
                            || $ext === 'html'){

                            $data = str_replace(
                                array_keys($replaces),
                                array_values($replaces),
                                $tmp->getContents()
                            );

                            $tmp->open('w+');
                            $tmp->write($data);
                            $tmp->close();
                        }
                    }
                }
            }
        }
        closedir($dir);
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $name = $input->getArgument('name');
        /** @var $dialog DialogHelper */
        $dialog = $this->getHelperSet()->get('dialog');
        if (!$name){
            $name = $dialog->ask(
                $output,
                'Enter the name of the new application: ',
                ''
            );
        }

        $this->writeln('Create app: `%s`', $name);
        $name = File::sanitize($name);
        if (!trim($name)){
            $this->writeln('[error] \'%s\' - incorrect name for an application', $name);
            return;
        }

        $this->writeln();
        $jquery = $dialog->askConfirmation($output, 'Do you want to include jQuery (y/n)?', false);
        if ($jquery)
            $this->writeln('    jQuery - yes');

        $this->writeln();
        $bootstrap = $dialog->askConfirmation($output, 'Do you want to include Bootstrap (y/n)?', false);
        if ($bootstrap)
            $this->writeln('    Bootstrap - yes');

        $console = $this->getApplication();

        if ($console->apps[$name]){
            $this->writeln('[error: application already exists]');
        }else{
            $fileApp = new File(Application::getApplicationsPath() . '/' . $name);

            if($fileApp->exists())
                $this->writeln('[error: application folder `%s` already exists, delete it and retry]', $fileApp->getPath());

            $fileApp->mkdirs();
            $pathApp = $fileApp->getPath();

            $DEPS = '';
            if ($jquery){
                if ($DEPS)
                    $DEPS .= ",\n      ";

                $DEPS .= '"jquery": {"version": "1.*"}';
            }

            if ($bootstrap){
                if ($DEPS)
                    $DEPS .= ",\n      ";

                $DEPS .= '"bootstrap": {"version": "2.*|3.*"}';
            }

            $replaces = array(
                '{%SECRET_KEY%}' => String::randomRandom(32, 48, true, true),
                '{%APP_NAME%}' => $name,
                '{%DEPS%}' => $DEPS,
                '{%INC_JQUERY%}' => $jquery ? '{deps.asset \'jquery\'}' : '',
                '{%INC_BOOTSTRAP%}' => $bootstrap ? '{deps.asset \'bootstrap\'}' : '',
            );

            self::recursive_copy(
                REGENIX_ROOT . 'console/.resource/template',
                $pathApp,
                $replaces
            );

            $console->registerApps();

            $this->writeln();
            $console->executeAndDisplay('load', array('name' => $name));
            $console->registerCurrentApp();
            $this->writeln();

            $console->executeAndDisplay('propel');
            $this->writeln();

            $console->executeAndDisplay('deps', array('command' => 'update'));
            $this->writeln();

            $this->writeln('[ok] Application `%s` has been created!', $name);
            $this->writeln('[ok] Open `localhost/%s` in your browser to see the application', $name);
            $this->writeln();
            $this->writeln('    (!) You can change this address by `app.rules` in application.conf');
            exit(0);
        }
    }
}