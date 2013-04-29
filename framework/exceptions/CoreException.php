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

        $class = get_called_class();
        return new $class(vsprintf($message, $args));
    }

    public function getSourceLine(){
        return $this->getLine();
    }

    public function getSourceFile(){
        return $this->getFile();
    }

    public static function findProjectStack(\Exception $e){
        $project    = Project::current();
        if ($project){
            $projectDir = str_replace('\\', '/', $project->getPath());
            $moduleDir  = ROOT . 'modules/';
            foreach($e->getTrace() as $stack){
                $dir = str_replace('\\', '/', dirname($stack['file']));
                if (strpos($dir, $projectDir) === 0){
                    return $stack;
                }
                if (strpos($dir, $moduleDir) === 0){
                    return $stack;
                }
            }
        }
        return null; //current($e->getTrace());
    }

    private static $files = array();
    private static $offsets = array();

    /**
     * create error mirror file
     * @param string $original file path
     * @param string $file
     */
    public static function setMirrorFile($original, $file){
        $original = str_replace('\\', '/', $original);
        self::$files[$original] = $file;
    }

    /**
     * @param string $original file path
     * @param int $offset
     */
    public static function setMirrorOffsetLine($original, $offset){
        $original = str_replace('\\', '/', $original);
        self::$offsets[$original] = $offset;
    }

    /**
     * @param $original file path
     * @return string
     */
    public static function getErrorFile($original){
        $original = str_replace('\\', '/', $original);
        if ($file = self::$files[$original])
            return $file;

        return $original;
    }

    /**
     * @param $original file path
     * @return int
     */
    public static function getErrorOffsetLine($original){
        $original = str_replace('\\', '/', $original);
        if ($offset = self::$files[$original])
            return (int)$offset;

        return 0;
    }
}

abstract class StrictObject {

    public function __set($name, $value){
        throw CoreException::formated('Property `%s` not defined in `%s` class', $name, get_class($this));
    }

    public function __get($name){
        throw CoreException::formated('Property `%s` not defined in `%s` class', $name, get_class($this));
    }
}