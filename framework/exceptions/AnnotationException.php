<?php
namespace regenix\exceptions;

use regenix\lang\CoreException;
use regenix\mvc\Annotations;
use regenix\lang\String;


class AnnotationException extends CoreException {

    const type = __CLASS__;

    /** @var integer */
    protected $line;

    /** @var string path */
    protected $file;

    public function __construct(Annotations $annotations, $name, $message){
        $this->file = $annotations->getFile();
        $this->line = $annotations->getLine();

        $msg = String::format('[@%s annotation] %s', $name, $message);
        parent::__construct($msg);
    }

    public function getSourceLine(){
        return $this->line;
    }

    public function getSourceFile(){
        return $this->file;
    }
}