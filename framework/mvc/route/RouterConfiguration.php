<?php

namespace framework\mvc\route;

use framework\config\Configuration;

class RouterConfiguration extends Configuration {

    const type = __CLASS__;

    private static $routePattern = '#^(GET|POST|PUT|DELETE|OPTIONS|HEAD|WS|\*)[(]?([^)]*)(\))?\s+(.*/[^\s]*)\s+([^\s(]+)(.+)?(\s*)$#';

    public function loadData(){
        
        $files = $this->files;
        if ( !$files )
            $files = array($this->file);
        
        foreach($files as $prefix => $file){
            
            if (!$file->exists()) continue;
            
            $handle = fopen($file->getAbsolutePath(), "r+");
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
                
                if (is_numeric($prefix)){
                    if ($action[0] != '.')
                        $action = '.controllers.' . $action;
                } else {
                    $action = $prefix . $action;
                }
                
                $this->data[] = array(
                    'method'  => $method, 
                    'headers' => $headers,
                    'path'    => $path,
                    'action'  => $action,
                    'params'  => $params
                );
            }
            fclose($handle);
        }
    }
    
    public function getRouters(){
        return $this->data;
    }
}