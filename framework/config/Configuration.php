<?php

namespace framework\config;

use framework\io\FileIOException;
use framework\utils\StringUtils;
use framework\io\File;

class Configuration {

    /**
     * @var File
     */
    protected $file;

    /** @var array */
    protected $data = array();


    protected function loadData(){
        //
        throw new \Exception(StringUtils::format('Can`t loadData() in abstract configuration'));
    }

    public function __construct(File $file = null){

        $this->setFile($file);
        if ( $file != null )
            $this->load();
    }

    public function setFile(File $file){
        $this->file = $file;
    }

    public function load(){

        $this->clear();

        if ($this->file == null || !$this->file->canRead() ){
            
            //throw new ConfigurationReadException($this, "can't open file " . $this->file);
            throw new FileIOException( $this->file );
        } else
            $this->loadData();
    }


    public function clear(){
        $this->data = array();
    }
}
