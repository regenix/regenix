<?php

namespace regenix\console;

use regenix\Application;
use regenix\lang\ArrayTyped;
use regenix\lang\String;

abstract class ConsoleCommand {

    const type  = __CLASS__;
    const GROUP = '';

    /** @var Application */
    protected $app;

    /** @var ArrayTyped */
    protected $args;

    /** @var ArrayTyped */
    protected $opts;

    /** @var string */
    protected $method;

    abstract public function __default();

    /**
     * @param $method
     * @param Application $app
     * @param array $args
     * @param array $options
     */
    public function __loadInfo($method, $app, array $args, array $options){
        $this->method = $method;
        $this->app = $app;
        $this->args = new ArrayTyped($args);
        $this->opts = new ArrayTyped($options);
        $this->onBefore();
    }

    abstract public function getInlineHelp();
    public function invokeHelp(){
        $this->writeln('    Help: ' . $this->getInlineHelp());
    }


    protected function onBefore(){}

    protected function write($message){
        fwrite(CONSOLE_STDOUT, '    ' . String::formatArgs($message, array_slice(func_get_args(), 1)));
        return $this;
    }

    protected function writeln($message = ''){
        $this->write(String::formatArgs($message, array_slice(func_get_args(), 1)) . "\n");
        return $this;
    }

    protected function read(){
        return fgets(STDIN);
    }
}