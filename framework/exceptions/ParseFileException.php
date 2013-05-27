<?php
namespace regenix\exceptions;

use regenix\lang\CoreException;
use regenix\lang\String;

class ParseFileException extends CoreException {

    protected $file;

    public function __construct($file, $message){
        $this->file = $file;
        parent::__construct(String::format('Parse error file `%s`, %s', $file, $message));
    }

    public function getSourceFile(){
        return ROOT . $this->file;
    }

    public function getSourceLine(){
        return 0;
    }
}