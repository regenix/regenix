<?php
namespace regenix\mvc\http;

use regenix\core\Regenix;
use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\mvc\binding\BindStaticValue;

class UploadFile extends File implements BindStaticValue {

    /** @var array */
    protected $meta;
    protected $uploadName;

    /** @var File */
    protected $uploadFile;

    /**
     * @param string $uploadName
     * @param array $meta
     * @throws \regenix\lang\CoreException
     */
    public function __construct($uploadName, $meta){
        $this->uploadName = $uploadName;
        $this->meta       = $meta;
        if ($meta['error']) {
            throw new CoreException(
                "Cannot upload file '$uploadName', see upload_max_filesize and post_max_size options in your php.ini"
            );
        }

        parent::__construct($meta['tmp_name']);
    }

    /**
     * @return bool
     */
    public function isEmpty(){
        return !$this->meta;
    }

    /**
     * @param $uploadUrl
     * @return \regenix\lang\File
     * @return File
     */
    public static function buildFromUrl($uploadUrl){
        if (($p = strpos($uploadUrl, '?')) !== false)
            $uploadUrl = substr($uploadUrl, 0, $p);

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
     * @return bool
     */
    public function isImage() {
        $ext = $this->getMimeExtension();
        return $ext == 'jpeg' || $ext == 'jpg' || $ext == 'png' || $ext == 'gif';
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
            return static::convertPathToUrl($this->uploadFile->getPath());
        } else {
            return null;
        }
    }

    public static function convertPathToUrl($path){
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
        $result = $body->getFile($name);
        if ($result)
            return $result;
        else
            return new UploadFile($name, array());
    }
}