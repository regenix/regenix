<?php
namespace regenix\exceptions;

use regenix\lang\CoreException;
use regenix\lang\File;

class WrappedException extends CoreException {

    /** @var CoreException */
    protected $cause;

    /** @var File */
    protected $file;

    /** @var int */
    protected $line;

    public function __construct(CoreException $e, File $file, $line) {
        $this->cause = $e;
        $this->file = $file;
        $this->line = $line;
        parent::__construct("");
    }

    public function getSourceLine() {
        return $this->line;
    }

    public function getSourceFile() {
        return $this->file->getPath();
    }

    public function getTitle(){
        $info = new \ReflectionClass($this->cause);
        return $info->getShortName();
    }

    public function getDescription(){
        return $this->cause->getMessage();
    }
}
