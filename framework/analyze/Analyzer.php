<?php
namespace regenix\analyze;


use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\lang\String;

abstract class Analyzer {

    const type = __CLASS__;

    /** @var File */
    protected $file;

    /** @var AnalyzeManager */
    protected $manager;

    /** @var \PHPParser_NodeAbstract[] */
    protected $statements;

    /** @var string */
    protected $content;

    public function __construct(AnalyzeManager $manager, File $file, array $statements, $content){
        $this->file = $file;
        $this->content = $content;
        $this->manager = $manager;
        $this->statements = $statements;
    }

    abstract function analyze();

    public function getSort(){
        return 0;
    }
}

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
}

class ParseException extends AnalyzeException {}