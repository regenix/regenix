<?php

namespace regenix\mvc\route;

use regenix\Application;
use regenix\Regenix;
use regenix\lang\StrictObject;
use regenix\cache\SystemCache;
use regenix\lang\String;
use regenix\logger\Logger;
use regenix\mvc\Controller;
use regenix\mvc\Request;
use regenix\mvc\RequestBinder;

class Router extends StrictObject {

    const type = __CLASS__;

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

    /** @var array route info */
    public $current = null;
    
    /** @var string */
    public $action;


    public function __construct() {
        ;
    }
    
    public function applyConfig(RouterConfiguration $config){
        foreach($config->getRouters() as $info){
            $this->addRoute($info['method'], $info['path'], str_replace('\\', '.', $info['action']), $info['params']);
        }
    }
    
    private function buildRoute($method, $path, $action, $params = ''){
        
        $_path = str_replace(array('{', '}'), array('{#', '{'), $path);
        $_args = explode('{', $_path);
        
        // /users/{id}/{module}/
        
        $args    = array();
        $pattern = '';
        $types   = array();
        $patterns = array();
        $tmpPattern = '';
        $url     = '';
        foreach($_args as $i => $arg){
            if ( $arg[0] == '#' ){
                if (($p = strpos($arg, '<')) !== false ){
                    $name  = substr($arg, 1, $p - 1);
                    $pattern .= ($tmpPattern = '(' . substr($arg, $p + 1, strpos($arg, '>') - $p - 1) . ')');
                } else {
                    $name  = substr($arg, 1);
                    $pattern .= ($tmpPattern = '([^/]+)');
                }

                if ( strpos($name, ':') === false ){
                    $args[] = $name;
                    $types[$name] = 'auto';
                } else {
                    $name = explode(':', $name, 3);

                    $types[$name[0]] = $name[1];
                    $args[] = $name = $name[0];
                }
                $url .= '{' . $name . '}';
                $patterns[$name] = $tmpPattern;
            } else {
                $url     .= $arg;
                $pattern .= $arg;
            }
        }

        $item = array(
                'method'  => strtoupper($method),
                'path'    => $path,
                'action'  => str_replace('\\', '.', $action),
                'params'  => $params,
                'types'   => $types,
                'pattern' => '#^' . $pattern . '$#',
                'args'    => $args,
                'patterns' => $patterns,
                'url'     => $url
        );

        return $item;
    }

    /**
     * @param $action
     * @param array $args
     * @param string $method
     * @return mixed|null|string
     */
    public function reverse($action, array $args = array(), $method = '*'){
        $originalAction = $action;

        if ($action !== null){
            $action = strtolower($action);
            $action = str_replace('\\', '.', $action);

            if ($action[0] != '.')
                $action = '.controllers.' . $action;
        }

        $originArgs = $args;
        foreach($this->routes as $route){
            $args = $originArgs;

            if ($method != '*' && $route['method'] != '*' && strtoupper($method) != $route['method'])
                continue;

            $replace = array('_METHOD');
            $to      = array($method == '*' ? 'GET' : strtoupper($method));
            $routeKeys = array_keys($route['types']);

            if ($action){
                $cur = $route['action'];
                foreach($route['patterns'] as $param => $regex){
                    $cur = str_replace('{' . $param . '}', $regex, $cur);
                }

                // search args in route address
                preg_match_all('#^' . $cur . '$#i', $originalAction, $matches);
                foreach($matches as $i => $el){
                    if ($i){
                        $args[$routeKeys[$i-1]] = current($el);
                    }
                }

                foreach($args as $key => $value){
                    if ($route['types'][$key]){
                        $replace[] = '{' . $key . '}';
                        $to[]      = $value;
                    }
                }
                $curAction = str_replace($replace, $to, $route['action']);
            }

            if ( $action === null || $action === strtolower(str_replace('\\', '.', $curAction)) ){
                $match = true;

                if ($action)
                foreach($route['patterns'] as $name => $pattern){
                    if (!preg_match('#^'. $pattern . '$#', (string)$args[$name])){
                        $match = false;
                        break;
                    }
                }

                if ($match){
                    $url = str_replace($replace, $to, $action === null ? '' : $route['url']);
                    $i = 0;
                    foreach($args as $key => $value){
                        if (!$route['types'][$key]){
                            $url .= ($i == 0 ? '?' : '&');
                            if (is_array($value)){
                                $kk = 0;
                                foreach($value as $k => $el){
                                    $kk += 1;
                                    $url .= $key . '[' . $k . ']=' . urlencode($el) . ($kk < sizeof($value) ? '&' : '');
                                }
                            } else {
                                $url .= $key . '=' . urlencode($value);
                            }
                            $i++;
                        }
                    }

                    //$src  = Regenix::src();
                    //$path = $src ? $src->getUriPath() : '';

                    return /*($path === '/' ? '' : $path) . */$url;
                }
            }
        }
        return null;
    }
    
    public function addRoute($method, $path, $action, $params = ''){
        $this->routes[] = $this->buildRoute($method, $path, $action, $params);
    }

    public function invokeMethod(Controller $controller, \ReflectionMethod $method){
        $args       = array();
        $parsedBody = null;

        // bind params for method call
        $controller->callBindParams($parsedBody);

        foreach($method->getParameters() as $param){
            $name = $param->getName();
            if ( isset($this->args[$name]) ){
                $value = $this->args[$name];
                if ( $type = $this->current['types'][$name] ){
                    if ( $type == 'auto' ){
                        $class = $param->getClass();
                        if ( $class !== null ){
                            $value = RequestBinder::getValue($value, $class->getName());
                        } else if ($param->isArray()){
                            $value = array($value);
                        }
                    } else
                        $value = RequestBinder::getValue($value, $type);
                }
                $args[$name] = $value;
            } else {
                $class = $param->getClass();

                /*if ( $class && $class->isSubclassOf(RequestBindParams::type) ){
                    $cls_name = $class->getName();
                    $value    = $cls_name::current();
                    $args[$name] = $value;
                } else*/ if ( $param->isArray() ){
                    $args[$name] = $controller->query->getArray($name);
                } else if ( $parsedBody && ($v = $parsedBody[$name]) ){
                    if ($class !== null)
                        $args[$name] = RequestBinder::getValue($v, $class->getName());
                    else
                        $args[$name] = $v;
                } else if ( !$parsedBody && $controller->query->has($name) ){
                    // получаем данные из GET
                    if ( $class !== null )
                        $value = $controller->query->getTyped($name, $class->getName());
                    else
                        $value = $controller->query->get($name);
                    $args[$name] = $value;
                }
            }
        }
        return $method->invokeArgs($controller, $args);
    }

    public function route(Request $request){
        $isCached = REGENIX_IS_DEV !== true;
        if ($isCached){
            $hash  = $request->getHash();
            $datas = SystemCache::get('routes');

            if ( $datas === null )
                $datas = array();

            $data = $datas[ $hash ];

            if ( $data !== null ){
                $this->args   = $data['args'];
                $this->action = $data['action'];
                return;
            }
        }
        
        $method = $request->getMethod();
        $path   = $request->getPath();
        $format = '';
        $domain = $request->getHost();

        foreach($this->routes as $route){
            $args = self::routeMatches($route, $method, $path, $format, $domain);
            
            if ( $args !== null ){
                $this->args    = $args;
                $this->current = $route;
                $this->action  = $route['action'];
                if (strpos($this->action, '{') !== false){
                    foreach ($args as $key => $value){
                        $this->action = str_replace('{' . $key . '}', $value, $this->action);
                    }
                }
                
                if ( $isCached ){
                    $datas[ $hash ] = array('args'=>$args, 'action'=>$this->action);
                    SystemCache::set('routes', $datas);
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
            $args['_METHOD'] = $method;
            return $args;
        }
        return null;
    }

    /**
     * @param string $action
     * @param array $args
     * @param string $method
     * @return null|string URL of action
     */
    public static function path($action, array $args = array(), $method = '*'){
        $router = Regenix::app()->router;
        return $router ? $router->reverse($action, $args, $method) : null;
    }
}
