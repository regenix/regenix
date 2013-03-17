<?php

namespace framework\io;

use framework\utils\MIMETypes;

class File {

    const type = __CLASS__;

    private $path;
    private $extension = null;

    /**
     * 
     * @param string $path
     * @param \framework\io\File $parent
     */
    public function __construct($path, File $parent = null){

        if ( $parent != null )
            $this->path = $parent->getPath() . $path;
        else
            $this->path = $path;
    }

    /**
     * get basename of file
     * @return string
     */
    public function getName(){
        return basename( $this->path );
    }

    /**
     * get original path of file
     * @return string
     */
    public function getPath(){
        return $this->path;
    }

    /**
     * get real path of file
     * @return string
     */
    public function getAbsolutePath(){
        return realpath( $this->path );
    }
    
    /**
     * get file extension in lower case
     * @return string file ext
     */
    public function getExtension(){
        
        if ( $this->extension !== null)
            return $this->extension;
        
        $p = strrpos( $this->path, '.' );
        if ( $p === false )
            return $this->extension = '';
        
        return $this->extension = (substr( $this->path, $p + 1 ));
    }
    
    /**
     * get http mime type
     * @return string
     */
    public function getMimeType(){
        
        return MIMETypes::getByExt( $this->getExtension() );
    }

    /**
     * check file exists
     * @return boolean
     */
    public function exists(){
        return file_exists($this->path);
    }

    /**
     * @return boolean
     */
    public function canRead(){
        return is_readable($this->path);
    }
    
    /**
     * @return boolean
     */
    public function isFile(){
        return is_file($this->path);
    }

    /**
     * recursive create dirs
     * @return boolean - create dir
     */
    public function mkdirs(){

        if ( !$this->exists() )
            return false;

        return mkdir($this->path, 0777, true);
    }

    /**
     * non-recursive create dirs
     * @return boolean
     */
    public function mkdir(){

        if ( !$this->exists() )
            return false;

        return mkdir($this->path, 0777, false);
    }
    
    /**
     * @return integer get file size in bytes
     */
    public function length(){
        if ( $this->isFile() ){
            return filesize($this->path);
        } else
            return -1;
    }
    
    /**
     * Get last modified of file in unix time format
     * @return int unix time
     */
    public function lastModified(){
        
        return filemtime($this->path);
    }
    
    
    public function __toString() {
        return sprintf( 'io\\File("%s")', $this->path );
    }
}
