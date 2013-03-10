<?php

namespace framework\cache;

class APCCache extends AbstractCache {
    
    protected $speed  = self::SPEED_VERYFAST;
    protected $atomic = true;

    protected function getCache($name) {
        return apc_fetch($name);
    }
    
    public function flush($full = false) {

        if ( $this->toClear )
            apc_clear_cache();
        else {

            foreach ($this->set as $name => $set){
                apc_store($name, array($set[0], $set[1]), $set[1]);
            }

            foreach ($this->remove as $name => $val ){
                apc_delete($name);
            }
        }
        
        $this->set     = array();
        $this->remove  = array();
        $this->toClear = false;
    }

    public static function canUse() {
        
        return extension_loaded('apc');
    }
}
