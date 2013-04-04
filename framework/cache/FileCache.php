<?php

namespace framework\cache;

use framework\Core;

class FileCache extends AbstractCache {

    const type = __CLASS__;

    protected $speed  = self::SPEED_SLOW;
    protected $atomic = false;


    /** @var string */
    private $cacheDir;
    
    /** @var array */
    private $index = array();

    public function __construct() {
        parent::__construct();
       
        $this->cacheDir = Core::$tempDir . 'cache/';
        if ( !is_dir($this->cacheDir) ){
            
            if ( !mkdir($this->cacheDir, 0777, true) ){
                
            }
            chmod($this->cacheDir, 0777);
        }
        
        $indexFile = $this->cacheDir . 'index.json';
        if (file_exists($indexFile) ){
            $this->index = json_decode(file_get_contents($indexFile), true);
        }
    }
    
    protected function getFile($name){
        return $this->cacheDir . sha1($name) . '.cache';
    }
    
    public function flush($full = false) {
        /*
        if ( $full ){
            $resouce = new \SplFileInfo($this->cacheDir . 'index.json');
            $file = $resouce->openFile('w');
            
            $file->fwrite(json_encode($this->index));
            $file->fflush();
        }*/
        
        foreach($this->set as $name => $set){
            
            $fileName = $this->getFile($name);
            try {
                $resouce = new \SplFileInfo($fileName);
                $file = $resouce->openFile('w');
                $file->fwrite( serialize(array($set[0], $set[1])) );
                $file->fflush();
                
                touch($fileName, $this->now + $set[1]);
            } catch (ErrorException $e){
                
            }
        }
        
        foreach ($this->remove as $name => $val){
            
            $fileName = $this->getFile($name);
            if (file_exists($fileName))
                unlink($fileName);
        }
        
        $this->set     = array();
        $this->remove  = array();
        $this->toClear = false;
    }

    protected function getCache($name) {
        $fileName = $this->getFile($name);
        $time = file_exists($fileName) ? filemtime($fileName) : 0;
        if ( $time === 0 )
            return null;
        
        if ( $time < $this->now ){
            $this->remove($name);
            return null;
        } else {
            $data = file_get_contents($fileName);
            return unserialize($data);
        }
    }

    public static function canUse() {
        return true;
    }
}