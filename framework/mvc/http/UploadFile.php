<?php
namespace regenix\mvc\http;

use regenix\core\Regenix;
use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\mvc\binding\BindStaticValue;

class UploadFile extends File implements BindStaticValue {

    protected $meta;
    protected $uploadName;

    /** @var File */
    protected $uploadFile;

    public function __construct($uploadName, $meta){
        $this->uploadName = $uploadName;
        $this->meta       = $meta;

        parent::__construct($meta['tmp_name']);
    }

    /**
     * @param $uploadUrl
     * @return \regenix\lang\File
     * @return File
     */
    public static function buildFromUrl($uploadUrl){
        $file = new File(ROOT . $uploadUrl);
        return $file->isFile() ? $file : null;
    }

    /**
     * @param $uploadUrl
     */
    public static function deleteFromUrl($uploadUrl){
        $tmp = static::buildFromUrl($uploadUrl);
        if ($tmp)
            $tmp->delete();
    }

    /**
     * get upload mime type
     * @return string
     */
    public function getMimeType(){
        return $this->meta['type'];
    }

    /**
     * get extension by mime type
     * @return string
     */
    public function getMimeExtension(){
        return MimeTypes::getByMimeType($this->getMimeType());
    }

    /**
     * Get user upload file name
     * @param null $suffix
     * @return mixed
     */
    public function getUserName($suffix = null){
        return basename($this->meta['name'], $suffix);
    }

    /**
     * @return mixed
     */
    public function getUserNameWithoutExtension(){
        return $this->getUserName('.' . $this->getUserExtension());
    }

    /**
     * Get real extension
     * @return string
     */
    public function getUserExtension(){
        $tmp = new File($this->getUserName());
        return $tmp->getExtension();
    }

    /**
     * @return int
     */
    public function length(){
        return $this->meta['size'];
    }

    /**
     * Get uploaded file, after call doUpload...
     * @return File
     */
    public function getUploadedFile(){
        return $this->uploadFile;
    }

    /**
     * @return string
     */
    public function getUploadedPath(){
        return $this->uploadFile ? $this->uploadFile->getPath() : '';
    }

    /**
     * Get url of uploaded file
     * @return string|null
     */
    public function getUploadedUrl(){
        if ($this->uploadFile){
            return static::convertToPathToUrl($this->uploadFile->getPath());
        } else {
            return null;
        }
    }

    public static function convertToPathToUrl($path){
        $src = str_replace(array('//', '///', '////', '/////'), '/', $path);
        return str_replace(ROOT, '/', $src);
    }

    /**
     * Move upload file to new filename
     * @param $filename
     * @return bool
     */
    protected function moveTo($filename){
        return move_uploaded_file($this->getPath(), $filename);
    }

    /**
     * @param string $prefix
     * @param null|string $uploadPath
     * @return bool
     * @throws \regenix\lang\CoreException
     */
    public function doUpload($prefix = '', $uploadPath = null){
        $uploadPath = $uploadPath ? $uploadPath : Regenix::app()->getPublicPath();

        $ext = $this->getMimeExtension();
        if (!$ext)
            $ext = $this->getUserExtension();

        $filename = File::sanitize($this->getUserNameWithoutExtension())
            . md5($_SERVER["REMOTE_ADDR"] . $this->getUserName() . time())
            . ($ext ? '.' . $ext : '');

        $fullPath = new File($uploadPath . $prefix);
        if (!$fullPath->isDirectory()){
            if (!$fullPath->mkdirs()){
                throw new CoreException('Can`t create upload directory for "%s" prefix', $prefix);
            }
        }

        $this->uploadFile = new File($fullPath->getPath() . '/' . $filename);
        return $this->moveTo($this->uploadFile->getPath());
    }

    /**
     * @param $fileName
     * @param string $prefix
     * @param null $uploadPath
     * @return bool
     * @throws \regenix\lang\CoreException
     */
    public function doUploadToFile($fileName, $prefix = '', $uploadPath = null){
        $uploadPath = $uploadPath ? $uploadPath : Regenix::app()->getPublicPath();

        $ext = $this->getMimeExtension();
        if (!$ext)
            $ext = $this->getUserExtension();

        $fullPath = new File($uploadPath . $prefix);
        if (!$fullPath->isDirectory()){
            if (!$fullPath->mkdirs()){
                throw new CoreException('Can`t create upload dir for "%s" prefix', $prefix);
            }
        }

        $this->uploadFile = new File($fullPath->getPath() . '/' . $fileName);
        return $this->moveTo($fullPath->getPath() . '/' . $fileName);
    }

    /**
     *
     */
    public function deleteUploaded(){
        $file = $this->getUploadedFile();
        if ($file)
            $file->delete();
    }

    /**
     * @param $value string
     * @param null $name
     * @return null
     */
    public static function onBindStaticValue($value, $name = null) {
        $body = RequestBody::getInstance();
        return $body->getFile($name);
    }
}