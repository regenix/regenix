<?php
namespace framework\console;

use framework\Core;
use framework\Project;
use framework\console\commands\AboutCommand;
use framework\console\commands\DepsCommand;
use framework\console\commands\HelpCommand;
use framework\console\commands\InfoCommand;
use framework\console\commands\LoadCommand;
use framework\exceptions\CoreException;
use framework\lang\IClassInitialization;

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
     * @var Project[]
     */
    public $projects = array();

    /**
     * @var Project
     */
    protected $project;

    protected function __construct(){
        echo "\n";
        $this->_registerProjects();
        $this->_registerCurrentProject();
    }

    private function _registerProjects(){
        $dirs = scandir(Project::getSrcDir());
        foreach ((array)$dirs as $dir){
            if ($dir[0] == '.' || $dir == '..') continue;
            $this->projects[ $dir ] = new Project( $dir, false );
        }
    }

    private function _registerCurrentProject(){
        $this->project = @getenv('REGENIX_CUR_PROJECT');
        $this->project = $this->projects[$this->project];
        if (!$this->project){
            $this->project = current($this->projects);
            putenv('REGENIX_CUR_PROJECT');
        }
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
            throw CoreException::formated('Command `%s` not found', $command);
        }

        /*$method = $this->args[1];
        if (!$method)*/
            $method = '__default';

        /** @var $cmd ConsoleCommand */
        $cmd = self::$commands[$command];
        if (!method_exists($cmd, $method)){
            throw CoreException::formated('Command method `%s %s` not found', $command, $method);
        }

        $cmd->__loadInfo($method, $this->project, (array)array_slice($this->args, 1), (array)$this->options);
        call_user_func(array($cmd, $method));
    }

    private static $commands = array();

    public static function register(ConsoleCommand $commands){
        $reflection = new \ReflectionClass($commands);
        $group = $reflection->getConstant('GROUP');
        if (!$group)
            throw CoreException::formated('Console commands GROUP can not empty');

        self::$commands[$group] = $commands;
    }

    public static function initialize() {
        self::register(new HelpCommand());
        self::register(new InfoCommand());
        self::register(new AboutCommand());
        self::register(new LoadCommand());
        self::register(new DepsCommand());
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