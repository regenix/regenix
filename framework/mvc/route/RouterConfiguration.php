<?php

namespace regenix\mvc\route;

use regenix\config\Configuration;
use regenix\config\ConfigurationReadException;
use regenix\exceptions\ActionNotFoundException;
use regenix\lang\ClassScanner;
use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\lang\String;

class RouterConfiguration extends Configuration {

    const type = __CLASS__;

    private static $routePattern = '#^(GET|POST|PUT|PATCH|DELETE|OPTIONS|HEAD|\*)[(]?([^)]*)(\))?\s+(.*/[^\s]*)\s+([^\s(]+)(.+)?(\s*)$#';

    /** @var File[] */
    private $modules = array();

    /** @var array */
    private $patterns = array();

    /** @var File */
    private $patternDir;

    public function addModule($code, $prefixNamespace, File $routeFile){
        if ($routeFile != null)
            $this->modules[$code] = array('prefix' => $prefixNamespace, 'file' => $routeFile);
        else
            $this->modules[$code] = true;
    }

    public function addPattern($code, $routes){
        if ($code === 'module')
            throw new CoreException('The code of a route pattern cannot be equal to `%s`', $code);

        $this->patterns[ $code ] = $routes;
    }

    public function addPatterns(array $patterns){
        if (REGENIX_IS_DEV){
            foreach($patterns as $code => $routes)
                $this->addPattern($code, $routes);
        } else {
            $this->patterns = array_merge($this->patterns, $patterns);
        }
    }

    public function setPatternDir(File $dir){
        $this->patternDir = $dir;
    }

    public function validate(){
        foreach($this->data as $route){
            $action = $route['action'];
            $class  = substr($action, 0, strrpos($action, '.'));
            $method = substr($action, strrpos($action, '.') + 1);

            if (strpos($class, '{') === false && strpos($class, '}') === false){
                if (!ClassScanner::find(str_replace('.', '\\', $class))){
                    throw new ActionNotFoundException($class . '.*');
                }

                if (strpos($method, '{') === false && strpos($method, '}') === false){
                    if(!method_exists(str_replace('.', '\\', $class), $method)){
                        throw new ActionNotFoundException($class . '.' . $method . '()');
                    }
                }
            }
        }
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

                $params = substr(trim($params), 1, -1);
                $params   = explode(',', $params);
                $args = array();
                foreach($params as $el){
                    list($key, $val) = explode(':', $el, 2);
                    $args[trim($key)] = trim($val);
                }
                $params = $args;

                if (String::startsWith($action, 'module:')){
                    $module = trim(substr($action, 7));
                    $info   = $this->modules[$module];

                    if (!isset($info)){
                        throw new ConfigurationReadException($this, 'Unknown route module: "' . $buffer . '"');
                    }

                    if ($info === true) continue;

                    $tmpRouter = new RouterConfiguration();
                    $tmpRouter->setFile($info['file']);
                    $tmpRouter->setPatternDir(new File($info['file']->getParent() . '/routes/'));
                    $tmpRouter->load();

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
                } else if (strpos($action, ':') !== false ){

                    $tmp = explode(':', $action, 2);
                    $pattern = $tmp[0];
                    $base    = trim($tmp[1]);

                    $patternRoutes = $this->patterns[$pattern];
                    if (!$patternRoutes){
                        $patternConfig = new RouterConfiguration();

                        $patternConfig->setFile(new File(str_replace('.', '/', $pattern) . '.route', $this->patternDir));
                        $patternConfig->setPatternDir($this->patternDir);
                        $patternConfig->load();

                        $patternRoutes = ($this->patterns[$pattern] = $patternConfig->getRouters());
                    }

                    if ($patternRoutes){
                        if ($base[0] != '.')
                            $base = '.controllers.' . $base;

                        $_keys = array_map(function($value){
                            return '[' . $value . ']';
                        }, array_keys($params));

                        $_keys[] = '[parent]';

                        foreach($patternRoutes as $el){
                            $relative = $el['path'][0] === '/';

                            if ($base !== '*' && $base !== '.controllers.*')
                                $el['action'] = $base . $el['action'];
                            else {
                                if ($relative)
                                    $el['path'] = substr($el['path'],1);
                            }

                            if ($relative){
                                $el['path'] = $path . $el['path'];
                            } else {
                                $el['path'] = '/' . str_replace(
                                    $_keys, array_merge($params, array('parent' => $path)), $el['path']
                                );
                            }

                            $this->data[] = $el;
                        }

                        continue;
                    } else
                        throw new CoreException('Cannot find `%s` route pattern', $pattern);
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