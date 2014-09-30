<?php

namespace regenix\config;

use regenix\exceptions\FileIOException;
use regenix\lang\StrictObject;
use regenix\lang\String;
use regenix\lang\File;

class Configuration extends StrictObject {

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
        throw new \Exception(String::format('Can`t invoke loadData() in abstract configuration'));
    }

    /**
     * 
     * @param \regenix\lang\File $file|array files
     */
    public function __construct($file = null){
        if (is_array($file)){
            $this->file = null;
            $this->files = $file;
            if (count($file) > 0)
                $this->load();
            
        } else {
            if ($file !== null){
                $this->setFile($file);
                $this->load();
            }
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
            if ($this->file == null/* || !$this->file->canRead() #fix small bug, check it! */){
                throw new FileIOException( $this->file );
            } else
                $this->loadData();
        }
    }

    public function clear(){
        $this->data = array();
    }
}
