<?php

namespace framework\cache;

class DisableCache extends AbstractCache {

    const type = __CLASS__;
    
    protected $speed  = -1;
    protected $atomic = true;

    protected function getCache($name) {
        // nop
        return null;
    }

    public function flush($full = false) {
        // nop
    }
}