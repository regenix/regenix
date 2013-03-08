<?php

namespace framework\mvc\route;

use framework\config\Configuration;

class RouterConfiguration extends Configuration {
    
    private static $routePattern = '#^(GET|POST|PUT|DELETE|OPTIONS|HEAD|WS|\*)[(]?([^)]*)(\))?\s+(.*/[^\s]*)\s+([^\s(]+)(.+)?(\s*)$#';
    

    public function loadData(){
        
        $handle = fopen($this->file->getAbsolutePath(), "r+");
        while (($buffer = fgets($handle, 4096)) !== false) {

            $buffer = trim($buffer);
            if ( !$buffer || $buffer[0] == '#' )
                continue;
            
            $matches = array();
            preg_match_all(self::$routePattern, $buffer, $matches);
            
            $method  = $matches[1][0];
            $headers = $matches[2][0];
            $path    = $matches[4][0];
            $action  = $matches[5][0];
            $params  = $matches[6][0];
            
            $this->data[] = array(
                'method'  => $method, 
                'headers' => $headers,
                'path'    => $path,
                'action'  => $action,
                'params'  => $params
            );
        }
    }
    
    public function getRouters(){
        return $this->data;
    }
}