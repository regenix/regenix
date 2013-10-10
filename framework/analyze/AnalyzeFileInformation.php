<?php
namespace regenix\analyze;

use regenix\lang\File;

class AnalyzeFileInformation {

    protected $modified;
    protected $name;
    protected $length;

    public function __construct(File $file){
        $this->modified = $file->lastModified();
        $this->name = $file->getName();
        $this->length = $file->length();
    }

    public function getLength(){
        return $this->length;
    }

    public function getModified(){
        return $this->modified;
    }

    public function getName(){
        return $this->name;
    }
}