<?php

namespace framework\cache;

use framework\Project;
use framework\exceptions\CacheException;

class MemcacheCache extends AbstractCache {
 
    const TIMEOUT = 15;

    protected $compressed = false;
    protected $persistent = false;

    protected $speed  = self::SPEED_FAST;
    protected $atomic = true;
    
    /**
     * @var \Memcache
     */
    private $server;
    
    protected $stats;


    public static function canUse() {
        return extension_loaded('memcache');
    }

    public function __construct() {
        parent::__construct();
        $this->server = new \Memcache();
        
        // read from config
        $config  = Project::current()->config;
        
        $this->compressed = $config->getBoolean('memcache.compressed', false);
        $this->persistens = $config->getBoolean('memcache.persistent', false);
        $servers          = $config->getArray('memcache.servers');
        
        if ( $servers && count($servers) ){
            
            $w = 10;
            foreach ($servers as $i => $server) {
                list($host, $port) = explode(':', $server);
                if ( !$port )
                    $port = 11211;
                
                $this->server->addserver($host, $port, $this->persistent, $w, self::TIMEOUT);
                $w += 10;
            }
            
        } else {
            if ( $this->persistent )
                $this->server->pconnect('127.0.0.1', 11211, self::TIMEOUT);
            else
                $this->server->connect('127.0.0.1', 11211, self::TIMEOUT);
        }
        
        $this->stats = $this->server->getStats();
        if ( !$this->stats ){
            throw new CacheException('Unable to connect memcache server(s)');
        }
    }
    
    

    protected function getCache($name) {
        
        $data = $this->server->get(array($name));
        if ( $data === false )
            return null;
        
        return $data[$name];
    }
    

    public function flush($full = false) {
        
        if ( $this->toClear ){
            
            $this->server->flush();
            
        } else {
        
            foreach ($this->set as $name => $set){
                $this->server->set($name, $set, $this->compressed ? MEMCACHE_COMPRESSED : 0, $set[1]);
            }

            foreach ($this->remove as $name => $val){
                $this->server->delete($name);
            }
        }
        
        $this->set     = array();
        $this->remove  = array();
        $this->toClear = false;
    }
}
