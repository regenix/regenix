<?php

namespace framework\mvc\template;

abstract class BaseTemplate {
    
    protected $file;
    protected $args = array();

    const ENGINE_NAME = 'abstract';
    const FILE_EXT    = '???';
    
    public function __construct($templateFile) {
        $this->file = $templateFile;
    }
    
    public function getContent(){ return null; } 
    public function render(){}
    
 
    public function putArgs(array $args = array()){
        $this->args = $args;
    }
}
