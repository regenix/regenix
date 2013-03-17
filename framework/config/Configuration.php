<?php

namespace framework\config;

use framework\io\FileIOException;
use framework\lang\String;
use framework\io\File;

class Configuration {

    const type = __CLASS__;

    /**
     * @var File
     */
    protected $file;
    
    /** @var File[] */
    protected $files;

    /** @var array */
    protected $data = array();


    protected function loadData(){
        //
        throw new \Exception(String::format('Can`t loadData() in abstract configuration'));
    }

    /**
     * 
     * @param \framework\io\File $file|array files
     */
    public function __construct($file){

        if (is_array($file)){
            
            $this->file = null;
            $this->files = $file;
            if (count($file) > 0)
                $this->load();
            
        } else {
            $this->setFile($file);
            if ( $file != null )
                $this->load();
        }
    }

    public function setFile(File $file){
        $this->file = $file;
    }

    public function load(){

        $this->clear();
        if ( $this->files ){
            $this->loadData();
        } else {
            if ($this->file == null || !$this->file->canRead() ){
                //throw new ConfigurationReadException($this, "can't open file " . $this->file);
                throw new FileIOException( $this->file );
            } else
                $this->loadData();
        }
    }


    public function clear(){
        $this->data = array();
    }
}
