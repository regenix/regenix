<?php
namespace regenix\console\commands;

use regenix\console\Commander;
use regenix\console\ConsoleCommand;
use regenix\Application;
use regenix\lang\File;
use regenix\lang\String;

class NewCommand extends ConsoleCommand {

    const GROUP = 'new';

    private static function recurse_copy($src,$dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    self::recurse_copy($src . '/' . $file,$dst . '/' . $file);
                }
                else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    public function __default(){
        $name = $this->args->get(0);
        $this->write('Create app: `%s`', $name);

        $cmd = Commander::current();
        if ($cmd->apps[$name]){
            $this->writeln('[error: project already exists]');
        }else{
            $fileApp = new File(Application::getApplicationsPath() . '/' . $name);

            if($fileApp->exists())
                $this->writeln('[error: project folder already exists]');
            else{
                $fileApp->mkdirs();
                $pathApp = $fileApp->getAbsolutePath();

                self::recurse_copy(
                    REGENIX_ROOT . 'console/.resource/template',
                    $pathApp
                );

                $appConf = $pathApp . '/conf/application.conf';
                $data = file_get_contents($appConf);
                $data = str_replace('{%SECRET_KEY%}', String::randomRandom(32, 48, true, true), $data);
                file_put_contents($appConf, $data);

                $this->writeln('[success]');
            }
        }
    }

    public function getInlineHelp(){
        return 'create new project: `new <name>`';
    }
}