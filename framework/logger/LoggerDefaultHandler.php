<?php
namespace regenix\logger;

use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\lang\String;

class LoggerDefaultHandler implements LoggerHandler {

    private $fp;
    private $fps = array();
    private $division;
    private $logPath;

    public function __construct($logPath, $division = true){
        $this->division = $division;
        $file     = $logPath . 'system.log';
        $this->logPath = $logPath;
        $path     = new File(dirname($file));

        if (!$path->exists())
            if (!$path->mkdirs()){
                throw new CoreException('Can`t create `%s` directory for logs', $path->getPath());
            }
        $this->fp = fopen($file, 'a+');
    }

    public function __destruct(){
        fclose($this->fp);
    }

    public function writeLog($level, array $args){
        $message = String::formatArgs($args[0], array_slice($args, 1));
        $time = @date("[Y/M/d H:i:s]");
        $lv = Logger::getLevelString($level);
        $out = "$time($lv): $message" . PHP_EOL;
        fwrite($this->fp, $out);
        if ($this->division){
            $fp = $this->fps[ $level ];
            if (!$fp){
                $fp = $this->fps[ $level ] = fopen($this->logPath . $lv . '.log', 'a+');
            }
            fwrite($fp, "$time: $message" . PHP_EOL);
        }
    }
}
