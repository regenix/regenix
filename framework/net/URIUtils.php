<?php

namespace framework\net;

abstract class URIUtils {

    
    /**
     * @param string $query URI query
     * @return array
     */
    public static function parseQuery($query){
        $result = array();
        parse_str($query, $result);
        
        return $result;
    }
}