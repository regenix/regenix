<?php
namespace regenix\analyze\exceptions;

use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\lang\String;

class AnalyzeException extends CoreException {

    /** @var File */
    protected $file;

    /** @var int */
    protected $line;

    public function __construct(File $file, $line, $message){
        $this->file = $file;
        $this->line = $line;

        $args = array();
        if (func_num_args() > 3)
            $args = array_slice(func_get_args(), 3);

        parent::__construct(String::formatArgs($message, $args));
    }

    public function getSourceLine(){
        return $this->line;
    }

    public function getSourceFile(){
        return $this->file->getPath();
    }

    public function isHidden(){
        return true;
    }
}
