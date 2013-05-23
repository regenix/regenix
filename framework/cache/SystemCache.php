<?php
namespace framework\cache;

define('APC_ENABLED', extension_loaded('apc'));
define('XCACHE_ENABLED', extension_loaded('xcache'));

if (IS_CORE_DEBUG === true)
    define('SYSTEM_CACHED', false);
else {
    if (APC_ENABLED){
        if (PHP_SAPI === 'cli')
            define('SYSTEM_CACHED', ini_get('apc.enable_cli') === 'On');
        else
            define('SYSTEM_CACHED', true);
    } else
        define('SYSTEM_CACHED', (XCACHE_ENABLED));
}

define('FAST_SERIALIZE_ENABLE', extension_loaded('igbinary'));
define('SYSTEM_CACHE_TMP_DIR', sys_get_temp_dir() . '/regenix/syscache/');

if (!is_dir(SYSTEM_CACHE_TMP_DIR))
    mkdir(SYSTEM_CACHE_TMP_DIR, 0777, true);

class SystemCache {

    const type = __CLASS__;

    private static $id = '';

    public static function isCached(){
        return SYSTEM_CACHED === true;
    }

    public static function setId($id){
        self::$id = $id;
    }

    protected static function getFromFile($name){
        $file = SYSTEM_CACHE_TMP_DIR . sha1(self::$id . '.' . $name);
        if (file_exists($file)){
            $result = unserialize(file_get_contents($file));
            return $result ? $result : null;
        }
        return null;
    }

    protected static function setToFile($name, $value){
        $file = SYSTEM_CACHE_TMP_DIR . sha1(self::$id . '.' . $name);
        file_put_contents($file, serialize($value));
    }

    public static function get($name, $cacheInFiles = false){
        return SYSTEM_CACHED === true ? apc_fetch('$.s.' . self::$id . '.' . $name)
            : ($cacheInFiles ? self::getFromFile($name) : null);
    }
    
    public static function set($name, $value, $lifetime = 3600, $cacheInFiles = false){
        if ( SYSTEM_CACHED === true ){
            apc_store('$.s.' . self::$id . '.' . $name, $value, $lifetime);
        } elseif ($cacheInFiles){
            self::setToFile($name, $value);
        }
    }

    public static function remove($name){
        if (SYSTEM_CACHED === true)
            apc_delete($name);
    }
    
    public static function getWithCheckFile($name, $filePath, $cacheInFiles = false){
        if ( !SYSTEM_CACHED && !$cacheInFiles )
            return null;

        $result = self::get($name, $cacheInFiles);

        if ( $result ){
            $upd    = (int)self::get($name . '.$upd', $cacheInFiles);
            if ($upd === 0)
                return null;
            
            $mtime  = filemtime($filePath);
            if ( $upd == $mtime )
                return $result;
        }
        return null;
    }
    
    public static function setWithCheckFile($name, $value, $filePath, $lifetime = 3600, $cacheInFiles = false){
        if ( !SYSTEM_CACHED && !$cacheInFiles )
            return;

        self::set($name, $value, $lifetime, $cacheInFiles);
        
        if (file_exists($filePath)){
            $mtime = filemtime($filePath);
            self::set($name.'.$upd', $mtime, $lifetime, $cacheInFiles);
        }
    }
    
    public static function getFileContents($filePath, $lifetime = 3600){
        if ( SYSTEM_CACHED ){
            $sha1  = '$.s.file.' . sha1($filePath);
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
