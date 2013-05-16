<?php

namespace framework\io;


use framework\exceptions\CoreException;

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
     * get parent directory
     * @return File
     */
    public function getParent(){
       return new File(dirname($this->getPath()));
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
        if ( $this->exists() )
            return false;

        return mkdir($this->path, 0777, true);
    }

    /**
     * non-recursive create dirs
     * @return boolean
     */
    public function mkdir(){
        if ( $this->exists() )
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

    /**
     * Delete file or recursive remove directory
     * @return bool
     */
    public function delete(){
        if (is_dir($this->path)){
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileInfo) {
                $todo = ($fileInfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileInfo->getRealPath());
            }
        } else {
            unlink($this->path);
        }
        return file_exists($this->path);
    }

    private $handle = null;

    /**
     * @param string $mode
     * @throws FileIOException
     * @throws static
     */
    public function open($mode){
        if ($this->handle)
            throw CoreException::formated('File "%s" already open, close the file before opening', $this->getPath());

        $handle = fopen($this->getPath(), $mode);
        if (!$handle)
            throw new FileIOException($this);

        $this->handle = $handle;
    }

    /**
     * @param int $length
     * @return string
     * @throws FileNotOpenException
     * @return string
     */
    public function gets($length = 4096){
        if ($this->handle)
            return fgets($this->handle, $length);
        else
            throw new FileNotOpenException($this);
    }

    /**
     * @param int $length
     * @return string
     * @throws FileNotOpenException
     * @return string
     */
    public function read($length = 4096){
        if ($this->handle)
            return fread($this->handle, $length);
        else
            throw new FileNotOpenException($this);
    }

    /**
     * @param $data
     * @param null $length
     * @return int
     * @throws FileNotOpenException
     */
    public function write($data, $length = null){
        if ($this->handle)
            return fwrite($this->handle, (string)$data, $length);
        else
            throw new FileNotOpenException($this);
    }

    /**
     * @param $offset
     * @param int $whence
     * @return int
     * @throws FileNotOpenException
     */
    public function seek($offset, $whence = SEEK_SET){
        if ($this->handle)
            return fseek($this->handle, (int)$offset, $whence);
        else
            throw new FileNotOpenException($this);
    }

    /**
     * @return bool
     * @throws FileNotOpenException
     */
    public function isEof(){
        if ($this->handle)
            return feof($this->handle);
        else
            throw new FileNotOpenException($this);
    }

    /**
     * @throws FileNotOpenException
     * @return bool
     */
    public function close(){
        if ($this->handle)
            return fclose($this->handle);
        else
            throw new FileNotOpenException($this);
    }

    public function __toString() {
        return sprintf( 'io\\File("%s")', $this->path );
    }
}
