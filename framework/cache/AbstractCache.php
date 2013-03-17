<?php

namespace framework\cache;

abstract class AbstractCache {

    const type = __CLASS__;

    const MAX_COUNT = 1000;
    
    const SPEED_SLOW     = 0;
    const SPEED_MEDIUM   = 1;
    const SPEED_FAST     = 2;
    const SPEED_VERYFAST = 3;

    protected $set     = array();
    protected $remove  = array();
    protected $toClear = false;
    
    protected $speed  = self::SPEED_MEDIUM;
    protected $atomic = false;

    protected $now;

    /** @return boolean */
    public static function canUse(){
        return false;
    }
    
    /**
     * 
     * @param int $minSpeed SPEED_SLOW, SPEED_MEDIUM, SPEED_FAST, SPEED_VERYFAST
     * @return boolean
     */
    public function isSpeed($minSpeed){
        return $this->speed >= $minSpeed;
    }
    
    public function isFast(){
        return $this->speed >= self::SPEED_FAST; 
    }
    
    public function isVeryFast(){
        return $this->speed >= self::SPEED_VERYFAST;
    }
    
    public function isSlow(){
        return $this->speed >= self::SPEED_SLOW;
    }
    
    public function isMedium(){
        return $this->speed >= self::SPEED_MEDIUM;
    }
    
    public function isAtomic(){
        return $this->atomic;
    }

    public function __construct() {
        $this->now = time();
    }

    public function set($name, $value, $lifetime = '1h'){
        
        if ( $this->toClear )
            flush();
        
        if (is_string($lifetime))
            $lifetime = \framework\libs\Time::parseDuration($lifetime);
        
        $this->set[ $name ] = array($value, );
        unset($this->remove[$name]);
    }

    public function setTags($name, array $tags){
        
        foreach($tags as $tag){
            
            $tagName = '$.tags.' . $tag;
            $values  = $this->get($tagName, array());
            if ( !$values[ $tag ] )
                $values[ $name ] = 1;
            
            $this->set($tagName, $values);
        }
    }


    public function get($name, $def = null){
        
        if (count($this->remove))
            $this->flush();
        
        $get = $this->set[ $name ];
        if ( $get !== null && $get[1] >= $this->now){
            return $get[0];
        }
        
        $get = $this->getCache($name);
        if ( $get === null )
            return $def;
        
        $this->set[ $name ] = $get;
        return $get[0];
    }
    
    public function remove($name){
        
        if ( $this->toClear )
            $this->flush();
        else {
            unset($this->set[$name]);
            $this->remove[ $name ] = 1;
        }
    }
    
    public function removeByTag($tag){
        
        $tagName = '$.tags.' . $tag;
        $names = $this->get($tagName);
        if ( $names !== null ){
            foreach( $names as $name )
                $this->remove($name);
            
            $this->remove($tagName);
        }
    }

    public function removeByTags(array $tags){
        
        foreach($tags as $tag){
            $this->removeByTag($tag);
        }
    }

    public function clear(){
        
        $this->set    = array();
        $this->remove = array();
        $this->toClear = true;
    }
    
    /**
     * @return array [0] - value, [1] - lifetime in seconds
     */
    abstract protected function getCache($name);
    
    /**
     * flush all cache
     * @param boolean $full
     */
    abstract public function flush($full = false);   
}