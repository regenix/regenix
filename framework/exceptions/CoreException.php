<?php
namespace framework\exceptions;

use framework\Project;
use framework\lang\String;

class CoreException extends \Exception {

    const type = __CLASS__;

    public function __construct($message){
        parent::__construct($message);

        // TODO
    }
    
    public static function formated($message){
        
        $args = array();
        if (func_num_args() > 1){
            $args = array_slice(func_get_args(), 1);
        }
        
        return new CoreException(vsprintf($message, $args));
    }

    public function getSourceLine(){
        return $this->getLine();
    }

    public function getSourceFile(){
        return $this->getFile();
    }

    public static function findProjectStack(\Exception $e){
        $project    = Project::current();
        $projectDir = str_replace('\\', '/', $project->getPath());
        foreach($e->getTrace() as $stack){
            $dir = str_replace('\\', '/', dirname($stack['file']));
            if ( strpos($dir, $projectDir) === 0 ){
                return $stack;
            }
        }
        return current($e->getTrace());
    }
}
