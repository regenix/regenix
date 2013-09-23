<?php
namespace regenix\console\commands;

use regenix\console\Commander;
use regenix\console\Console;
use regenix\console\ConsoleCommand;
use regenix\Application;
use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\lang\String;

class NewCommand extends ConsoleCommand {

    const GROUP = 'new';

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
                            $ext === 'xml' || $ext === 'route' || $ext === 'lang' || $ext === ''){

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

    public function __default(){
        $name = $this->args->get(0);
        $this->writeln('Create app: `%s`', $name);

        $name = File::sanitize($name);
        if (!trim($name)){
            $this->writeln('[error] \'%s\' - incorrect name for an application', $name);
            return;
        }

        $cmd = Commander::current();
        if ($cmd->apps[$name]){
            $this->writeln('[error: application already exists]');
        }else{
            $fileApp = new File(Application::getApplicationsPath() . '/' . $name);

            if($fileApp->exists())
                $this->writeln('[error: application folder `%s` already exists, delete it and retry]', $fileApp->getPath());

            $fileApp->mkdirs();
            $pathApp = $fileApp->getPath();

            $replaces = array(
                '{%SECRET_KEY%}' => String::randomRandom(32, 48, true, true),
                '{%APP_NAME%}' => $name
            );

            self::recursive_copy(
                REGENIX_ROOT . 'console/.resource/template',
                $pathApp,
                $replaces
            );

            $commander = Commander::current();
            $commander->_registerApps();

            $this->writeln();
            $commander->run('load', array($name));
            $commander->_registerCurrentApp();
            $this->writeln();

            $commander->run('deps', array('update'));
            $this->writeln();

            $commander->run('propel');
            $this->writeln();

            $this->writeln('[ok] Application `%s` has been created!', $name);
            $this->writeln('[ok] Open `localhost/%s` in your browser to see the application', $name);
            $this->writeln();
            $this->writeln('    (!) You can change this address by `app.rules` in application.conf');
        }
    }

    public function getInlineHelp(){
        return 'create a new application: `regenix new <name>`';
    }
}