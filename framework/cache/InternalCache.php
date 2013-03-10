<?php

namespace framework\cache;

class SystemCache {

    
    public static function get($name){
        
        return INTERNAL_CACHE === true ? apc_fetch('$.sys.' . $name) : null;
    }
    
    public static function set($name, $value, $lifetime = 3600){
        
        if ( INTERNAL_CACHE === true )
            apc_store('$.sys.' . $name, $value, $lifetime);
    }
    
    public static function getWithCheckFile($name, $filePath){
        
        $result = self::get($name);
        if ( $result ){
            $upd    = (int)self::get($name . '.$upd');
            if ($upd === 0)
                return null;
            
            $mtime  = filemtime($filePath);
            if ( $upd == $mtime )
                return $result;
        }
        return null;
    }
    
    public static function setWithCheckFile($name, $value, $filePath, $lifetime = 3600){
        
        self::set($name, $value, $lifetime);
        
        if (file_exists($filePath)){
            $mtime  = filemtime($filePath);
            self::set($name.'.$upd', $mtime, $lifetime);
        }
    }
    
    public static function getFileContents($filePath, $lifetime = 3600){
        
        if ( INTERNAL_CACHE ){
            $sha1  = '$.sys.file.' . sha1($filePath);
            $inmem = apc_fetch($sha1 . '.$upd');
            if ( $inmem ){
                $mtime = file_exists($filePath) ? filemtime($filePath) : -1;
                if ( $inmem == $mtime ){
                    $result = apc_fetch($sha1, $success);
                    if ( $success )
                        return $result;
                }
            } else {
                
                $result = file_get_contents($filePath);
                if (file_exists($filePath)){
                    apc_store($sha1, $result, $lifetime);
                    apc_store($sha1 . '.$upd', filemtime($filePath), $lifetime);
                }
                return $result;
            }
        } else
            return file_get_contents($filePath);
    }
}

define('INTERNAL_CACHE', extension_loaded('apc'));
