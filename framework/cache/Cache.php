<?php
namespace regenix\cache;

use Jamm\Memory\APCObject;
use Jamm\Memory\CouchbaseObject;
use Jamm\Memory\KeyAutoUnlocker;
use Jamm\Memory\MemcacheObject;
use Jamm\Memory\MemoryObject;
use Jamm\Memory\RedisObject;
use Jamm\Memory\RedisServer;
use Jamm\Memory\Shm\SHMObject;
use regenix\Regenix;
use regenix\Application;
use regenix\lang\CoreException;
use regenix\lang\IClassInitialization;

final class Cache implements IClassInitialization {

    const DRIVER_NONE      = 0;
    const DRIVER_APC       = 1;
    const DRIVER_REDIS     = 2;
    const DRIVER_MEMCACHE  = 3;
    const DRIVER_SHM       = 4;

    /** @var MemoryObject */
    private static $mem;

    /** @var int */
    private static $driver;

    /** @var array */
    private static $defaultConfig;

    private function __construct(){}

    /**
     * @param $key
     * @param int $maxWaitSec
     * @return bool
     */
    public static function waitFor($key, $maxWaitSec = 5){
        if (!self::$mem)
            return true;

        $au = null;
        self::$mem->set_max_wait_unlock_time($maxWaitSec / 100);
        return self::$mem->acquire_key($key, $au);
    }

    /**
     * @param $name
     * @return KeyAutoUnlocker
     */
    public static function lockKey($name){
        if (!self::$mem)
            return false;

        $au = null;
        self::$mem->lock_key($name, $au);
        return $au;
    }

    /**
     * @param KeyAutoUnlocker $lock
     * @return bool
     */
    public static function unlockKey(KeyAutoUnlocker $lock){
        if (!self::$mem)
            return false;

        return self::$mem->unlock_key($lock);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param int $ttl in seconds
     * @param array $tags
     * @return bool
     */
    public static function set($name, $value, $ttl = 3600, array $tags = array()){
        if (!self::$mem)
            return false;

        return self::$mem->save($name, $value, $ttl, $tags);
    }

    /**
     * @param $name
     * @param $value
     * @param int $ttl
     * @return array|int|string
     */
    public static function inc($name, $value, $ttl = 3600){
        if (!self::$mem)
            return false;

        return self::$mem->increment($name, $value, 0, $ttl);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public static function get($name){
        if (!self::$mem)
            return false;

        return self::$mem->read($name);
    }

    /**
     * @return mixed
     */
    public static function getLastError(){
        if (!self::$mem)
            return false;

        return self::$mem->getLastErr();
    }


    /**
     * @return int
     */
    public static function getDriver(){
        return self::$driver;
    }

    /**
     * @return bool
     */
    public static function isWork(){
        return self::$mem !== null;
    }

    /**
     * @return array
     */
    public static function getStat(){
        if (!self::$mem)
            return array();

        return self::$mem->get_stat();
    }

    /**
     * @param string|array $name
     * @return array|bool
     */
    public static function remove($name){
        if (!self::$mem)
            return false;

        return self::$mem->del($name);
    }

    /**
     * @param $tag
     * @return bool
     */
    public static function removeByTag($tag){
        if (!self::$mem)
            return false;

        return self::$mem->del_by_tags((string)$tag);
    }

    /**
     * @param array $tags
     * @return bool
     */
    public static function removeByTags(array $tags){
        if (!self::$mem)
            return false;

        return self::$mem->del_by_tags($tags);
    }

    /**
     * @return bool
     */
    public static function removeOld(){
        if (!self::$mem)
            return false;

        return self::$mem->del_old();
    }

    /**
     * @param int $driver
     * @param array $config
     * @throws static
     */
    public static function setDriver($driver = self::DRIVER_APC, array $config = array()){
        $config = array_merge(self::$defaultConfig, $config);

        switch($driver){
            case self::DRIVER_NONE: {
                $mem = null;
            } break;
            case self::DRIVER_APC: {
                $mem = new APCObject((string)$config['id']);
            } break;
            case self::DRIVER_REDIS: {
                /** @var $mem RedisObject */
                $server = new RedisServer(
                    $config['host'] ? $config['host'] : 'localhost',
                    $config['port'] ? $config['port'] : '6379');
                $mem = new RedisObject((string)$config['id'], $server);
            } break;
            case self::DRIVER_MEMCACHE: {
                $mem = new MemcacheObject((string)$config['id'],
                    $config['host'] ? $config['host'] : 'localhost',
                    $config['port'] ? $config['port'] : '11211'
                );
            } break;
            case self::DRIVER_SHM: {
                $mem = new SHMObject((string)$config['id'], (int)$config['size'], (int)$config['maxsize']);
            } break;
            default: {
               throw CoreException::formated('Unknown cache driver type: #%s', $driver);
            }
        }
        self::$mem = $mem;
        self::$driver = $driver;
    }

    public static function initialize(){
        $app =  Regenix::app();
        if ($app){
            $driver  = $app->config->get('cache.driver', 'auto');

            self::$defaultConfig = array(
                'id'   => $app->config->getString('cache.id', ''),
                'host' => $app->config->get('cache.host', 'localhost'),
                'port' => $app->config->getNumber('cache.port'),
                'size' => $app->config->getNumber('cache.size'),
                'maxsize' => $app->config->getNumber('cache.maxsize')
            );

            switch(trim(strtolower($driver))){
                case '':
                case 'none': self::setDriver(self::DRIVER_NONE);
                    break;
                case 'apc': self::setDriver(self::DRIVER_APC); break;
                case 'shm': self::setDriver(self::DRIVER_SHM); break;
                case 'redis': self::setDriver(self::DRIVER_REDIS); break;
                case 'memcache':
                case 'memcached': self::setDriver(self::DRIVER_MEMCACHE); break;
                case 'auto': {
                    if (extension_loaded('apc'))
                        self::setDriver(self::DRIVER_APC);
                    else if (function_exists('shmop_open'))
                        self::setDriver(self::DRIVER_SHM);
                    else
                        self::setDriver(self::DRIVER_NONE);
                } break;
                default: {
                    throw CoreException::formated('Unknown cache driver type: #%s', $driver);
                }
            }
        }
    }
}