<?php

namespace framework\utils;


abstract class MIMETypes {

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