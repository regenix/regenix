<?php

namespace framework\mvc\route;

use framework\config\Configuration;
use framework\config\ConfigurationReadException;
use framework\lang\CoreException;
use framework\lang\File;
use framework\lang\String;

class RouterConfiguration extends Configuration {

    const type = __CLASS__;

    private static $routePattern = '#^(GET|POST|PUT|DELETE|OPTIONS|HEAD|WS|\*)[(]?([^)]*)(\))?\s+(.*/[^\s]*)\s+([^\s(]+)(.+)?(\s*)$#';

    /** @var File[] */
    private $modules = array();

    public function addModule($code, $prefixNamespace, File $routeFile){
        if ($routeFile != null)
            $this->modules[$code] = array('prefix' => $prefixNamespace, 'file' => $routeFile);
        else
            $this->modules[$code] = true;
    }

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

                if (String::startsWith($action, 'module:')){
                    $module = trim(substr($action, 7));
                    $info   = $this->modules[$module];

                    if (!isset($info)){
                        throw new ConfigurationReadException($this, 'Unknown route module: "' . $buffer . '"');
                    }

                    if ($info === true) continue;

                    $tmpRouter = new RouterConfiguration($info['file']);
                    $tmpRoutes = $tmpRouter->getRouters();
                    foreach($tmpRoutes as &$rule){
                        if (String::startsWith($rule['action'], '.controllers.')){
                            $rule['action'] = $info['prefix'] . substr($rule['action'], 13);
                        }
                        if (substr($path, -1) == '/')
                            $path = substr($path, 0, -1);

                        $rule['path'] = $path . $rule['path'];

                        $this->data[] = $rule;
                    }
                    continue;
                }
                
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