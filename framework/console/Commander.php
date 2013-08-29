<?php
namespace regenix\console;

use regenix\Regenix;
use regenix\Application;
use regenix\console\commands\AboutCommand;
use regenix\console\commands\DepsCommand;
use regenix\console\commands\HelpCommand;
use regenix\console\commands\InfoCommand;
use regenix\console\commands\LoadCommand;
use regenix\console\commands\TestCommand;
use regenix\lang\ClassScanner;
use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\lang\IClassInitialization;
use regenix\lang\String;

class Commander implements IClassInitialization {

    /**
     * @var array
     */
    protected $args;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var Application[]
     */
    public $apps = array();

    /**
     * @var Application
     */
    protected $app;

    protected function __construct(){
        echo "\n";
        $this->_registerapps();
        $this->_registerCurrentapp();
    }

    private function _registerApps(){
        $path = new File(Application::getApplicationsPath());
        foreach ($path->findFiles() as $path){
            if ($path->isDirectory()){
                $this->apps[ $path->getName() ] = new Application( $path, false );
            }
        }
    }

    private function _registerCurrentApp(){
        $tmpFile = new File(sys_get_temp_dir() . '/regenix/.current');

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

            $tmpFile->getParentFile()->mkdirs();
            file_put_contents($tmpFile->getPath(), $this->app->getName());
        }

        if ($this->app)
            ClassScanner::addClassPath($this->app->getSrcPath());
    }

    public function run(){
        global $argv;
        $argv = array_slice($argv, 1);

        foreach($argv as $arg){
            if ($arg[0] == '-'){
                $arg = explode('=', $arg, 2);
                if ($arg[1])
                    $this->options[ substr($arg[0], 1) ] = trim($arg[1]);
                else
                    $this->options[ substr($arg[0], 1) ] = true;
            } else {
                $this->args[] = $arg;
            }
        }

        $command = $this->args[0];
        if (!$command || !self::$commands[$command]){
            throw new CoreException('The command `%s` is not found', $command);
        }

        /*$method = $this->args[1];
        if (!$method)*/
            $method = '__default';

        /** @var $cmd ConsoleCommand */
        $cmd = self::$commands[$command];
        if (!method_exists($cmd, $method)){
            throw new CoreException('The command method `%s %s` is not found', $command, $method);
        }

        $cmd->__loadInfo($method, $this->app, (array)array_slice($this->args, 1), (array)$this->options);
        call_user_func(array($cmd, $method));
    }

    private static $commands = array();

    public static function register(ConsoleCommand $commands){
        $reflection = new \ReflectionClass($commands);
        $group = $reflection->getConstant('GROUP');
        if (!$group)
            throw new CoreException('Console commands: GROUP constant cannot be empty');

        self::$commands[$group] = $commands;
    }

    /**
     * @return ConsoleCommand[]
     */
    public static function getCommands(){
        return self::$commands;
    }

    public static function initialize() {
        $meta = ClassScanner::find(ConsoleCommand::type);
        foreach($meta->getChildrensAll() as $class){
            if (!$class->isAbstract())
                self::register($class->newInstance());
        }
    }

    private static $instance;

    /**
     * @return Commander
     */
    public static function current(){
        if (self::$instance)
            return self::$instance;

        return self::$instance = new Commander();
    }
}

final class Console {

    private function __construct(){}

    public static function write($message){
        fwrite(CONSOLE_STDOUT, '    ' . String::formatArgs($message, array_slice(func_get_args(), 1)));
    }

    public static function writeln($message = ''){
        self::write(String::formatArgs($message, array_slice(func_get_args(), 1)) . "\n");
    }

    public static function read(){
        return fgets(STDIN);
    }
}