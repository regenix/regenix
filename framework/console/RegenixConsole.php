<?php
namespace regenix\console;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application as ConsoleApplication;
use regenix\core\Regenix;
use regenix\lang\ClassScanner;
use regenix\lang\File;
use regenix\logger\Logger;
use regenix\core\Application;

class RegenixConsole extends ConsoleApplication {

    /**
     * @var Application[]
     */
    public $apps = array();

    /**
     * @var Application
     */
    public $app;

    public function __construct() {
        parent::__construct();
        register_shutdown_function(array($this, '__shutdownConsole'));

        $this->registerApps();
        $this->registerCurrentApp();

        $baseCommand = ClassScanner::find(RegenixCommand::type);
        $commands = $baseCommand->getAllChildren();
        foreach($commands as $command){
            if (class_exists($command->getName())) {
                $this->add($command->newInstance());
            }
        }
    }

    /**
     * @param $command
     * @param array $args
     * @return CommandTester
     */
    public function execute($command, array $args = array()){
        $command = $this->find($command);
        $commandTester = new CommandTester($command);
        $args['command'] = $command->getName();
        $commandTester->execute($args);
        return $commandTester;
    }

    /**
     * @param $command
     * @param array $args
     */
    public function executeAndDisplay($command, array $args = array()){
        $commandTester = $this->execute($command, $args);
        echo $commandTester->getDisplay(true);
    }

    public function registerApps(){
        $this->apps = array();
        $path = new File(Application::getApplicationsPath());
        foreach ($path->findFiles() as $path){
            if ($path->isDirectory()){
                $this->apps[ $path->getName() ] = new Application( $path, false );
            }
        }
    }

    public function registerCurrentApp(){
        $tmpFile = new File(Regenix::getTempPath() . '/regenix/.current');

        if ($tmpFile->isFile()){
            $this->app = @file_get_contents($tmpFile->getPath());
            $this->app = $this->apps[$this->app];
        }

        if (!$this->app){
            foreach($this->apps as $name => $app){
                if ($name[0] == '.') continue;

                $this->app = $app;
                break;
            }

            if (!$this->app)
                $this->app = current($this->apps);

            if ($this->app){
                $tmpFile->getParentFile()->mkdirs();
                file_put_contents($tmpFile->getPath(), $this->app->getName());
            }
        }

        if ($this->app){
            $this->app->config->setEnv($this->app->config->getString('app.mode', 'dev'));
            ClassScanner::addClassPath($this->app->getSrcPath());
            ClassScanner::addClassPath($this->app->getTestPath());
            ClassScanner::addClassPath($this->app->getPath() . 'vendor/');
            Logger::initialize($this->app);
        }
    }

    public static function __shutdownConsole(){
        $error = error_get_last();
        if ($error){
            switch($error['type']){
                case E_ERROR:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_PARSE:
                case E_USER_ERROR:
                case 4096:
                {
                    echo "\n    Error: '" . $error['message'] . "'";
                    echo "\n        file: '" . $error['file'] . "'";
                    echo "\n        line: " . $error['line'] . "\n";

                    break;
                }
                default: {
                return;
                }
            }
            echo "\n    Exit code: " . $error['type'];
            echo "\n";
            exit($error['type']);
        }
    }
}
