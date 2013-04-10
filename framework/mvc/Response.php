<?php

namespace framework\mvc;

use framework\Core;
use framework\StrongObject;
use framework\libs\Time;
use framework\mvc\providers\ResponseProvider;

class Response extends StrongObject {

    const type = __CLASS__;

    private $status;
    private $contentType;
    private $entity;
    private $httpVersion;
    private $charset;
    
    private $headers = array();

    public function __construct() {
        $this
             ->setStatus( 200 )
             ->setContentType( 'text/plain' )
             ->setHttpVersion( '1.1' )
             ->setCharset( 'UTF-8' )
             ->setEntity(null);
    }

    public function setEntity($object) {
        $this->entity = $object;
        return $this;
    }
    
    public function getEntity(){
        return $this->entity;
    }

    public function setCharset($charset){
        $this->charset = $charset;
        return $this;
    }

    public function setStatus($status){
        $this->status = (int)$status;
        return $this;
    }
    
    public function setHttpVersion($version){
        $this->httpVersion = $version;
        return $this;
    }

    public function setContentType($contentType){
        $this->contentType = $contentType;
        return $this;
    }
    
    public function setHeader($name, $value){
        $this->headers[$name] = $value;
        return $this;
    }
    
    public function setHeaders(array $headers){
        $this->headers = $headers;
        return $this;
    }

    public function applyHeaders(array $headers){
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function cacheFor($duration){
        $maxAge = Time::parseDuration($duration);
        $this->setHeader("Cache-Control", "max-age=" . $maxAge);
        return $this;
    }

    public function cacheForETag($etag, $duration, $lastModified = null){
        $this->cacheFor($duration);
        $this->setHeader("Last-Modified", gmdate("D, d M Y H:i:s ", $lastModified));
        $this->setHeader("Etag", $etag);
        return $this;
    }

    public function sendHeaders(){
        header('HTTP/' . $this->httpVersion . ' ' . (int)$this->status);
        header('Content-type: '. $this->contentType . '; charset=' . $this->charset);
        
        foreach($this->headers as $name => $value){
            header($name . ': ' . $value);
        }

        header('Powered-By: Regenix Framework v' . Core::VERSION);
    }

    public function send($headers = true){
        if ( is_object($this->entity) ){
            $providerClass = ResponseProvider::get(get_class($this->entity)); 
            
            $provider = new $providerClass($this);
            if ( $headers )
                $this->sendHeaders();
            
            $provider->onBeforeRender();
            $content  = $provider->getContent();
            
            if ( $content === null ){
                $provider->render();
            }
            
        } else {
            if ( $headers )
                $this->sendHeaders();
            $content = (string)$this->entity;
        }
        
        if ( $content != null ){
            echo $content;
        }
    }
}

abstract class MIMETypes {

    const type = __CLASS__;

    /**
     * @var array
     */
    protected static $exts = array(

        // applications
        'json'      => 'application/json',
        'js'        => 'application/javascript',
        'pdf'       => 'application/pdf',
        'zip'       => 'application/zip',
        'rar'       => 'application/x-tar',
        'gzip'      => 'application/x-gzip',
        'gz'        => 'application/x-gzip',
        'torrent'   => 'application/x-bittorrent',

        'doc'       => 'application/msword',
        'docx'      => 'application/msword',
        'rtf'       => 'application/msword',

        // audio
        'mp4'       => 'audio/mp4',
        'wav'       => 'audio/x-wav',
        'wave'      => 'audio/x-wav',
        'ogg'       => 'audio/ogg',
        'mp3'       => 'audio/mpeg',

        // video
        'avi'       => 'video/avi',
        'mpeg'      => 'video/mpeg',
        'mpg'       => 'video/mpeg',
        'mpe'       => 'video/mpeg',
        'mov'       => 'video/quicktime',
        'qt'        => 'video/quicktime',

        // images
        'bmp'       => 'image/bmp',
        'jpg'       => 'image/jpeg',
        'jpeg'      => 'image/jpeg',
        'gif'       => 'image/gif',
        'png'       => 'image/png',
        'swf'       => 'application/futuresplash',
        'tiff'      => 'image/tiff',

        'html'      => 'text/html',
        'htm'       => 'text/html',
        'phtml'     => 'text/html',
        'xml'       => 'text/xml',
        'css'       => 'text/css',
        'txt'       => 'text/plain',

        'exe'       => 'application/x-msdownload'
    );


    /**
     * get mime type from file extension
     * @param string $ext file extension without dot
     * @return string
     */
    public static function getByExt($ext){

        if (strpos( $ext, '.' ) === 0){
            $ext = substr($ext, 1);
        }

        return self::$exts[strtolower( $ext )];
    }

    /**
     * register new mime type for file extension
     * @param mixed $ext file extension(s)
     * @param string $mime mime type
     */
    public static function registerExtension($ext, $mime){

        self::$exts[ $ext ] = $mime;
    }
}