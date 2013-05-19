<?php
namespace framework\io;

use framework\Core;
use framework\exceptions\CoreException;
use framework\exceptions\StrictObject;
use framework\lang\String;

class File extends StrictObject {

    const type = __CLASS__;

    private $path;
    private $extension;

    /**
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
     * @param null|string $prefix
     * @return File
     */
    public static function createTempFile($prefix = null){
        if ($prefix === null){
            $prefix = String::random(5);
        }

        $path = tempnam(sys_get_temp_dir(), $prefix);
        return new File($path);
    }

    /**
     * get basename of file
     * @param null $suffix
     * @return string
     */
    public function getName($suffix = null){
        return basename( $this->path, $suffix );
    }

    /**
     * get basename of file without ext
     * @return string
     */
    public function getNameWithoutExtension(){
        return $this->getName('.' . $this->getExtension());
    }

    /**
     * get original path of file
     * @return string
     */
    public function getPath(){
        return $this->path;
    }

    /**
     * get parent directory as file object
     * @return File
     */
    public function getParentFile(){
       return new File(dirname($this->path));
    }

    /**
     * get parent directory as string
     * @return string
     */
    public function getParent(){
        return dirname($this->path);
    }

    /**
     * get real path of file
     * @return string
     */
    public function getAbsolutePath(){
        return realpath( $this->path );
    }

    /**
     * get real path as file object
     * @return File
     */
    public function getAbsoluteFile(){
        return new File($this->getAbsolutePath());
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
     * @return bool
     */
    public function isFile(){
        return is_file($this->path);
    }

    /**
     * @return bool
     */
    public function isDirectory(){
        return is_dir($this->path);
    }

    /**
     * @param File $new
     * @return bool
     */
    public function renameTo(File $new){
        return rename($this->getAbsolutePath(), $new->getAbsolutePath());
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
                @$todo($fileInfo->getRealPath());
            }
        } else {
            @unlink($this->path);
        }
        return !file_exists($this->path);
    }

    private $handle = null;

    /**
     * @param string $mode
     * @return resource
     * @throws FileIOException
     * @throws static
     */
    public function open($mode){
        if ($this->handle)
            throw CoreException::formated('File "%s" already open, close the file before opening', $this->getPath());

        $handle = fopen($this->path, $mode);
        if (!$handle)
            throw new FileIOException($this);

        $this->handle = $handle;
        return $handle;
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
            // wtf php ?
            return $length !== null
                ? fwrite($this->handle, (string)$data, $length)
                : fwrite($this->handle, (string)$data);
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

    /**
     * @param null|int $flags
     * @return string
     */
    public function getContents($flags = null){
        if ($this->exists() && $this->isFile())
            return file_get_contents($this->path, $flags);
        else
            return '';
    }

    /**
     * @param null|int $time
     * @param null|int $atime
     * @return bool
     */
    public function touch($time = null, $atime = null){
        return touch($this->path, $time, $atime);
    }


    /**
     * find files and dirs
     * @param string $pattern
     * @param int $flags
     * @return string[]
     */
    public function find($pattern = '.*', $flags = 0){
        $files = glob($this->path . '/' . $pattern, $flags);
        return $files;
    }

    /**
     * @param string $pattern
     * @param int $flags
     * @return File[]
     */
    public function findFiles($pattern = '.*', $flags = 0){
        $files = $this->find($pattern, $flags);
        if ($files)
        foreach($files as &$file){
            $file = new File($file);
        }
        return $files;
    }

    public function __toString() {
        return String::format( 'io\\File("%s")', $this->path );
    }
}
