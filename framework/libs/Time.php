<?php

namespace framework\libs;

class Time {

    const type = __CLASS__;

    /**
     * parse duration and get in seconds  
     * @param string $duration - string time 1h - hour, 3m - minute, 1s - second, 1d - day
     * @return int - the number of seconds
     */
    public static function parseDuration($duration){
        
        if (is_int($duration))
            return $duration;
        
        $time = (int)$duration;
        $len  = strlen($duration);
        switch ($duration[$len - 1]){
            
            case 'h': return $time * 60 * 60;
            case 's': return $time;
            case 'm': return $time * 60;
            case 'd': return $time * 60 * 60 * 24;
            default:
                return $time;
        }
    }
}
