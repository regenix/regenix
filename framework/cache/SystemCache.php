<?php
namespace framework\cache;

define('APC_ENABLED', extension_loaded('apc'));
define('XCACHE_ENABLED', extension_loaded('xcache'));

if (IS_CORE_DEBUG === true)
    define('SYSTEM_CACHED', false);
else
    define('SYSTEM_CACHED', (APC_ENABLED || XCACHE_ENABLED));

define('FAST_SERIALIZE_ENABLE', extension_loaded('igbinary'));

class SystemCache {

    const type = __CLASS__;

    private static $id = '';

    public static function setId($id){
        self::$id = $id;
    }

    public static function get($name){
        return SYSTEM_CACHED === true ?
            (FAST_SERIALIZE_ENABLE ? igbinary_unserialize(apc_fetch('$.fsys.' . self::$id . '.' . $name)) :
                apc_fetch('$.sys.' . self::$id . '.' . $name)) :
            null;
    }
    
    public static function set($name, $value, $lifetime = 3600){
        if ( SYSTEM_CACHED === true ){
            if ( FAST_SERIALIZE_ENABLE )
                apc_store('$.fsys.' . self::$id . '.' . $name, igbinary_serialize($value), $lifetime);
            else
                apc_store('$.sys.' . self::$id . '.' . $name, $value, $lifetime);
        }
    }
    
    public static function getWithCheckFile($name, $filePath){
        if ( !SYSTEM_CACHED )
            return null;

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
        if ( !SYSTEM_CACHED )
            return;

        self::set($name, $value, $lifetime);
        
        if (file_exists($filePath)){
            $mtime  = filemtime($filePath);
            self::set($name.'.$upd', $mtime, $lifetime);
        }
    }
    
    public static function getFileContents($filePath, $lifetime = 3600){
        
        if ( SYSTEM_CACHED ){
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

if(!function_exists('apc_store')){
    function apc_store($key, $var, $ttl = 0){
        return xcache_set($key, $var, $ttl);
    }
}
if(!function_exists('apc_fetch')){
    function apc_fetch($key, &$success=true){
        $success = xcache_isset($key);
        return xcache_get($key);
    }
}
if(!function_exists('apc_delete')){
    function apc_delete($key){
        return xcache_unset($key);
    }
}
if(!function_exists('apc_exists')){
    function apc_exists($keys){
        if(is_array($keys)){
            $exists = array();
            foreach($keys as $key){
                if(xcache_isset($key))
                    $exists[]=$key;
            }
            return $exists;
        }

        return xcache_isset($keys);
    }
}
