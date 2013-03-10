<?php

namespace framework\mvc\route;

use framework\mvc\Request;

class Router {

    /**
     * {
     *   [method] => POST | GET | DELETE | etc.
     *   [pattern] = /users/(.+)/ for /users/{id}/
     * }
     * @var array
     */
    private $routes = array();

    /** @var array */
    public $args = array();
    
    /** @var string */
    public $action;


    public function __construct() {
        ;
    }
    
    public function applyConfig(RouterConfiguration $config){
        
        foreach($config->getRouters() as $info){
            $this->addRoute($info['method'], $info['path'], $info['action'], $info['params']);
        }
    }
    
    private function buildRoute($method, $path, $action, $params = ''){
        
        $_path = str_replace(array('{', '}'), array('{#', '{'), $path);
        $_args = explode('{', $_path);
        
        // /users/{id}/{module}/
        
        $args    = array();
        $pattern = ''; 
        foreach($_args as $i => $arg){
            
            if ( $arg[0] == '#' ){
                
                if (($p = strpos($arg, '<')) !== false ){
                    $args[]   = substr($arg, 1, $p - 1);
                    $pattern .= '(' . substr($arg, $p + 1, strpos($arg, '>') - $p - 1) . ')';
                } else {
                    $args[]   = substr($arg, 1);
                    $pattern .= '([^/].+)';
                }
            } else {
                $pattern .= $arg;
            }
        }
        
        $item = array(
                'method'  => strtoupper($method),
                'path'    => $path,
                'action'  => $action,
                'params'  => $params,
                'pattern' => '#^' . $pattern . '$#',
                'args'    => $args
        );
        
        return $item;
    }
    
    public function addRoute($method, $path, $action, $params = ''){
        
        $this->routes[] = $this->buildRoute($method, $path, $action, $params);
    }

    public function route(Request $request){
        
        $isCached = IS_PROD;
        
        if ($isCached){
            $cache = c('Cache');
            
            if ( $isCached = ($cache->isFast() && $cache->isAtomic()) ){
                $hash  = $request->getHash();
                $datas = $cache->get('$.system.routes');
                
                if ( $datas === null )
                    $datas = array();
                
                $data = $datas[ $hash ];
                
                if ( $data !== null ){
                    $this->args   = $data['args'];
                    $this->action = $data['action']; 
                    return;
                }
            }
        }
        
        $method = $request->getMethod();
        $path   = $request->getPath();
        $format = '';
        $domain = $request->getHost();
        
        foreach($this->routes as $route){
            
            $args = self::routeMatches($route, $method, $path, $format, $domain);
            
            if ( $args !== null ){
                
                $this->args   = $args;
                $this->action = $route['action'];
                if (strpos($this->action, '{') !== false){
                    foreach ($args as $key => $value){
                        $this->action = str_replace('{' . $key . '}', $value, $this->action);
                    }
                }
                
                if ( $isCached ){
                    $datas[ $hash ] = array('args'=>$args, 'action'=>$this->action);
                    $cache->set('$.system.routes', $datas);
                }
                
                break;
            }
        }
    }
    
    private static function routeMatches($route, $method, $path, $format, $domain){
        
        if ( $method === null || $route['method'] == '*' || $method == $route['method'] ){
            
            $args = array();
            $result = preg_match_all($route['pattern'], $path, $matches);
            if (!$result)
                return null;
            
            foreach($matches as $i => $value){
                if ( $i === 0 )                    
                    continue;
                
                $args[ $route['args'][$i - 1] ] = $value[0];
            }
            return $args;
        }
        return null;
    }
}
