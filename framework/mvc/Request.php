<?php

namespace framework\mvc;

use framework\StrictObject;
use framework\exceptions\CoreException;
use framework\lang\ArrayTyped;
use framework\lang\String;
use framework\libs\Time;

class Request extends StrictObject {

    const type = __CLASS__;

    protected $method = "GET";
    protected $uri    = "";
    protected $host   = "";
    protected $userAgent = "";
    protected $referer   = "";
    protected $port      = "";
    protected $protocol  = "http";
    
    protected $basePath = "";

    /**
     * @var URL
     */
    protected $currentUrl;

    /**
     * @var array
     */
    protected $headers;

    public function __construct($headers = null) {
        $this->headers = $headers;
    }

    public static function createFromGlobal(){

        $headers = array();
        if ( function_exists('getallheaders') ){
            $headers = getallheaders();
        } else if ( function_exists('apache_request_headers') ){
            $headers = apache_request_headers();
        } else {
            foreach($_SERVER as $key=>$value) {
                if (substr($key,0,5)=="HTTP_") {
                    $key = str_replace(" ", "-", str_replace("_"," ",substr($key,5)));
                    $headers[$key] = $value;
                }
            }
        }
        $headers = array_change_key_case($headers, CASE_LOWER);

        $req = new Request($headers);
        $req->setMethod($_SERVER['REQUEST_METHOD']);
        $req->setUri($_SERVER['REQUEST_URI']);
        $req->host = $_SERVER['HTTP_HOST'];
        $req->userAgent = $_SERVER['HTTP_USER_AGENT'];
        $req->referer   = $_SERVER['HTTP_REFERER'];
        $req->port      = (int)$_SERVER['SERVER_PORT'];
        $req->protocol  = 'http'; // TODO //$_SERVER['SERVER_PROTOCOL'];
        
        $req->currentUrl = URL::buildFromUri( $req->host, $req->uri, $req->protocol, $req->port );
        return $req;
    }
    
    /** 
     * @return string calc hash of request
     */
    public function getHash(){
        return sha1(
                $this->method. '|' . 
                $this->protocol . '|' . 
                $this->host . '|' . 
                $this->port . '|' . $this->uri );
    }

    /**
     * @return string
     */
    public function getUri(){
        return $this->uri;
    }

    /**
     * @param $name
     * @param null $def
     * @return array|null
     */
    public function getHeader($name, $def = null){
        $name = strtolower($name);
        return isset($this->headers[$name]) ? $this->headers[$name] : $def;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasHeader($name){
        return isset($this->headers[strtolower($name)]);
    }
    
    /**
     * query from request uri
     * @return string
     */
    public function getQuery(){
        $tmp = explode('?', $this->uri, 2);
        return (string)$tmp[1];
    }
    
    /**
     * get request path
     * @return string
     */
    public function getPath(){
        $tmp = explode('?', $this->uri, 2);
        return (string)$tmp[0];
    }

        /**
     * @param string $url
     */
    public function setUri($url){
        $this->uri = $url;
        
        if ( $this->basePath ){
            $p = strpos($this->uri, $this->basePath);
            if ( $p === 0 )
                $this->uri = substr($this->uri, strlen($this->basePath));
            
            if ( !$this->uri )
                $this->uri = '/';
            else if ($this->uri[0] !== '/')
                $this->uri = '/' . $this->uri;
        }
    }

    /**
     * @return string
     */
    public function getHost(){
        return $this->host;
    }
    
    /**
     * @return string
     */
    public function getUserAgent(){
        return $this->userAgent;
    }
    
    /**
     * @return string
     */
    public function getReferer(){
        return $this->referer;
    }

    /**
     * @return string
     */
    public function getMethod(){
        return $this->method;
    }

    /**
     * get languages from accept-languages
     * @return array
     */
    public function getLanguages(){
        $langs = array();
        $info  = $this->headers['accept-language'];
        $types = explode(';', $info, 10);
        foreach($types as $type){
            $meta = explode(',', $type);
            if ( $meta[1] )
                $lang = $meta[1];
            elseif ( $meta[0] && substr($meta, 0, 2) != 'q=' )
                $lang = $meta[0];

            if ( strpos('-', $lang) !== false ){
                $lang = explode('-', $lang);
                $lang = $lang[0];
            }
            $langs[] = $lang;
        }
        return $langs;
    }
    
    /**
     * @param string $method - get, post, etc.
     * @return \framework\mvc\Request
     */
    public function setMethod($method){
        $this->method = strtoupper($method);
        return $this;
    }

    public function setBasePath($path){
        
        $this->basePath = $path;
        $this->setUri($this->getUri());
        
        return $this;
    }

    /**
     * @param string $method - post, get, put, delete, etc.
     * @return boolean
     */
    public function isMethod($method){
        return $this->method === $method;
    }
    
    /**
     * 
     * @param array $methods
     * @return boolean
     */
    public function isMethods(array $methods){
        foreach ($methods as $value) {
            if ( $value === $this->method )
                return true;
        }
        return false;
    }


    /**
     * @param string $etag
     * @return bool
     */
    public function isCachedEtag($etag){
        $tagHead = 'if-none-match';
        if ($this->hasHeader($tagHead)){
            $rTag = $this->getHeader($tagHead);
            return $rTag === $etag;
        } else
            return false;
    }
    
    /**
     * @param string|URL $url
     * @return boolean
     */
    public function isBase($baseUrl){
        if ( !($baseUrl instanceof URL) )
            $baseUrl = new URL($baseUrl);
        
        return $this->currentUrl->constaints($baseUrl);
    }

    private static $instance;
    
    /**
     * get current request
     * @return Request
     */
    public static function current(){
        if ( self::$instance )
            return self::$instance;
        
        return self::$instance = Request::createFromGlobal();
    }
}

/**
 * TODO: DI
 * Class Session
 * @package framework\mvc
 */
class Session extends StrictObject {

    private $init = false;

    protected function __construct(){
    }

    protected function check(){
        if (!$this->init){
            session_start();
            $this->init = true;
        }
    }

    /**
     * @return string
     */
    public function getId(){
        $this->check();
        return session_id();
    }

    /**
     * @return array
     */
    public function all(){
        $this->check();
        return (array)$_SESSION;
    }

    /**
     * @param string $name
     * @param mixed $def
     * @return null|scalar
     */
    public function get($name, $def = null){
        $this->check();
        return $this->has($name) ? $_SESSION[$name] : $def;
    }

    /**
     * @param $name string
     * @param $value string|int|float|null
     */
    public function put($name, $value){
        $this->check();
        $_SESSION[$name] = $value;
    }

    /**
     * @param array $values
     */
    public function putAll(array $values){
        $this->check();
        foreach($values as $name => $value){
            $this->put($name, $value);
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has($name){
        $this->check();
        return isset($_SESSION[$name]);
    }

    /**
     * @param string $name
     */
    public function remove($name){
        $this->check();
        unset($_SESSION[$name]);
    }

    /**
     * clear all session values
     */
    public function clear(){
        $this->check();
        session_unset();
    }

    private static $current;
    /**
     * @return Session
     */
    public static function current(){
        if ( self::$current )
            return self::$current;
        return self::$current = new Session();
    }
}


class Flash extends StrictObject {

    /** @var Session */
    private $session;

    protected function __construct(){
        $this->session = Session::current();
    }

    /**
     * @param null $value
     * @return $this|scalar|null
     */
    public function success($value = null){
        if ( $value === null )
            return $this->get("success");
        else
            return $this->put("success", $value);
    }

    /**
     * @param null $value
     * @return $this|scalar|null
     */
    public function error($value = null){
        if ( $value === null )
            return $this->get("error");
        else
            return $this->put("error", $value);
    }

    /**
     * @param null $value
     * @return $this|scalar|null
     */
    public function warning($value = null){
        if ( $value === null )
            return $this->get("warning");
        else
            return $this->put("warning", $value);
    }

    /**
     * @param string $name
     * @param scalar $value
     * @return $this
     */
    public function put($name, $value){
        $this->session->put($name . '$$flash', $value);
        $this->session->put($name . '$$flash_i', 1);
        return $this;
    }

    /**
     * keep flash value
     * @param string $name
     * @param int $inc
     * @return $this
     */
    public function keep($name, $inc = 1){
        $i = $this->session->get($name . '$$flash_i');
        if ( $i !== null ){
            $i = (int)$i + $inc;
            if ( $i < 0 ){
                $this->remove($name);
            } else {
                $this->session->put($name . '$$flash_i', $i);
            }
        }
        return $this;
    }

    public function touch($name){
        return $this->keep($name, -1);
    }

    public function touchAll(){
        $all = $this->session->all();
        foreach($all as $key => $value){
            if ( String::endsWith($key, '$$flash') ){
                $this->touch(substr($key, 0, -7));
            }
        }
        return $this;
    }

    public function get($name, $def = null){
        return $this->session->get($name . '$$flash', $def);
    }

    /**
     * exists flash value
     * @param string $name
     * @return bool
     */
    public function has($name){
        return $this->session->has($name . '$$flash');
    }

    /**
     * hard remove flash value
     * @param $name
     * @return $this
     */
    public function remove($name){
        $this->session->remove($name . '$$flash');
        $this->session->remove($name . '$$flash_i');
        return $this;
    }

    private static $current;
    /**
     * Current flash object
     * @return Flash
     */
    public static function current(){
        if ( self::$current )
            return self::$current;
        return self::$current = new Flash();
    }
}

class Cookie extends StrictObject {

    /**
     * @var ArrayTyped
     */
    private $data;

    protected function __construct(){
        $this->data = new ArrayTyped($_COOKIE);
    }

    /**
     * @param string $name
     * @param string|int|float|boolean $value
     * @param null|int|string $expires
     */
    public function put($name, $value, $expires = null){
        setcookie($name, $value, $expires ? Time::parseDuration($expires) : $expires );
        $this->data = new ArrayTyped($_COOKIE);
    }

    /**
     * @return ArrayTyped
     */
    public function all(){
        return $this->data;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has($name){
        return $this->data->has($name);
    }

    /**
     * @param string $name
     * @param null $def
     * @return mixed
     */
    public function get($name, $def = null){
        return $this->data->get($name, $def);
    }

    /**
     * @param string $name
     * @param string $def
     * @return string
     */
    public function getString($name, $def = ''){
        return $this->data->getString($name, $def);
    }

    /**
     * @param string $name
     * @param bool $def
     * @return bool
     */
    public function getBoolean($name, $def = false){
        return $this->data->getBoolean($name, $def);
    }

    /**
     * @param string $name
     * @param int $def
     * @return int
     */
    public function getInteger($name, $def = 0){
        return $this->data->getInteger($name, $def);
    }

    /**
     * @param string $name
     * @param float $def
     * @return float
     */
    public function getDouble($name, $def = 0.0){
        return $this->data->getDouble($name, $def);
    }

    /**
     * @var Cookie
     */
    private static $current;

    public static function current(){
        if (self::$current)
            return self::$current;

        return self::$current = new Cookie();
    }
}

class RequestQuery extends StrictObject {
    
    /** @var Request */
    private $request;
    
    /** @var array */
    private $args = null;


    public function __construct() {
        $this->request = Request::current();
        $this->args = URL::parseQuery( $this->request->getQuery() );
    }
    
    /**
     * get all query arguments
     * @return array
     */
    public function getAll(){
        return $this->args;
    }

    /**
     * checks exists name arg
     * @param $name
     * @return bool
     */
    public function has($name){
        return isset($this->args[$name]);
    }

    /**
     * get one query argument
     * @param string $name
     * @param mixed $def
     * @return mixed
     */
    public function get($name, $def = null){
        $arg  = $this->args[$name];
        return $arg === null ? $def : $arg;
    }

    /**
     * get typed bind value
     * @param $name
     * @param $type
     * @param null $def
     * @return bool|float|RequestBindValue|int|string
     */
    public function getTyped($name, $type, $def = null){
        return RequestBinder::getValue($this->get($name, $def), $type);
    }
    
    /**
     * get integer typed query argument
     * @param string $name
     * @param integer $def
     * @return integer
     */
    public function getNumber($name, $def = 0){
        return (int)$this->get($name, (int)$def);
    }
    
    /**
     * get string typed query argument
     * @param string $name
     * @param string $def
     * @return string
     */
    public function getString($name, $def = ''){
        return (string)$this->get( $name, (string)$def );
    }
    
    
    /**
     * 
     * @param string $name
     * @param boolean $def
     * @return boolean
     */
    public function getBoolean($name, $def = false){
        return (boolean)$this->get($name, (boolean)$def);
    }
    
    /**
     * get array query argument
     * @param string $name
     * @param array $def
     * @return array
     */
    public function getArray($name, array $def = array()){
        $arg = $this->get($name, (array)$def);
        if (is_array( $arg ))
            return $arg;
        else
            return array($arg);
    }
    
    /**
     * get array typed from query string
     * @param string $name
     * @param string $type string|boolean|integer|double|array
     * @param array $def
     * @return array
     */
    public function getArrayTyped($name, $type = 'string', array $def = array()){
        $arg = $this->getArray($name, $def);
        foreach($arg as &$v){
            $v = RequestBinder::getValue($v, $type);
        }
        return $arg;
    }
    
    /**
     * get array from explode of query argument
     * @param string $name
     * @param string $delimiter
     * @param array $def
     * @return array
     */
    public function getExplode($name, $delimiter = ',', array $def = array()){
        $arg = $this->get($name, null);
        if ( $arg === null || is_array( $arg) )
            return (array)$def;
        
        return explode($delimiter, (string)$arg, 300);
    }
    
    /**
     * get array typed from explode of query argument
     * @param string $name
     * @param string $type
     * @param string $delimiter
     * @param array $def
     * @return array
     */
    public function getExplodeTyped($name, $type = 'string', $delimiter = ',', array $def = array()){
        
        $arg = $this->getExplode($name, $delimiter, $def);
        foreach($arg as &$v){
            $v = RequestBinder::getValue($v, $type);
        }
        return $arg;
    }

    private static $instance;

    /**
     * @return RequestQuery
     */
    public static function current(){
        if (self::$instance)
            return self::$instance;

        return self::$instance = new RequestQuery();
    }
}


abstract class RequestBinder extends StrictObject {

    /**
     * @param $value string
     * @param $type string
     */
    public static function getValue($value, $type){
        switch($type){
            case 'int':
            case 'integer':
            case 'long': {
                return (int)$value;
            } break;

            case 'double':
            case 'float': {
                return (double)$value;
            } break;

            case 'bool':
            case 'boolean': {
                return (boolean)$value;
            } break;

            case 'string':
            case 'str': {
                return (string)$value;
            } break;

            case 'array': {
                return array($value);
            } break;

            default: {
                $type = str_replace('.', '\\', $type);
                if ( class_exists($type) ){
                    $instance = new $type;
                    if ( $instance instanceof RequestBindValue ){
                        $instance->onBindValue($value);
                        return $instance;
                    } else
                        throw new BindValueInstanceException($type);
                } else
                    throw new BindValueException($value, $type);
            }
        }
    }
}

class BindValueException extends CoreException {
    public function __construct($value, $type){
        parent::__construct(String::format('Can\'t bind `%s` value as `%s` type', (string)$value, $type));
    }
}

class BindValueInstanceException extends CoreException {
    public function __construct($type){
        parent::__construct(String::format(
            'Bind error: `%s` class must be implements \framework\mvc\RequestBindValue interface for bind value', $type
        ));
    }
}

interface RequestBindValue {
    /**
     * @param $value string
     * @return null
     */
    public function onBindValue($value);
}

class RequestBody extends StrictObject {

    const type = __CLASS__;

    protected $data = null;

    public function __construct(){
        $this->data = file_get_contents('php://input');
    }

    /**
     * parse data as json
     * @return array
     */
    public function asJSON(){
        return json_decode($this->data, true);
    }

    /**
     * parse data as query string
     * @return ArrayTyped
     */
    public function asQuery(){
        $result = array();
        parse_str((string)$this->data, $result);
        return new ArrayTyped($result);
    }

    /**
     * get data as string
     * @return string
     */
    public function asString(){
        return (string)$this->data;
    }
}

abstract class RequestBindParams extends StrictObject {

    const type     = __CLASS__;

    static $method = 'GET';
    static $prefix = '';

    public function __construct(array $args, $prefix = ''){
        foreach($args as $key => $value){
            if ( $prefix ) {
                if (($p = strpos($key, $prefix)) === 0){
                    $key = substr($key, strlen($prefix));
                } else
                    continue;
            }

            if ( method_exists($this, 'set' . $key) ){
                $reflect = new \ReflectionMethod(get_class($this), 'set' . $key);
                $a       = array();
                foreach($reflect->getParameters() as $param){
                    if($class = $param->getClass()){
                        $a[] = RequestBinder::getValue($value, $class->getName());
                    } else
                        $a[] = $value;
                }
                $reflect->setAccessible(true);
                $reflect->invokeArgs($this, $a);
            } else if ( property_exists($this, $key) ) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * @param null|string $prefix - if null default used
     * @internal param null $method
     * @internal param null|string $method - if null default used
     * @return RequestBindParams
     */
    public static function current($prefix = null){
        $class   = get_called_class();
        $_method = strtoupper($class::method);
        switch($_method){
            case 'POST': {
                $body = new RequestBody();
                $httpArgs = $body->asQuery();
            } break;
            case 'GET': {
                $tmp = new RequestQuery();
                $httpArgs = $tmp->getAll();
            } break;
            case 'REQUEST': {
                $httpArgs = $_REQUEST;
            } break;
            case 'COOKIE': {
                $httpArgs = $_COOKIE;
            } break;
            case 'SESSION': {
                $httpArgs = $_SESSION;
            } break;
            case 'FILES': {
                $httpArgs = $_FILES;
            } break;
        }
        return new $class( $httpArgs, $prefix ? $prefix : $class::prefix );
    }
}

class URL extends StrictObject {

    const type = __CLASS__;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $host;

    /**
     * @var integer
     */
    private $port;

    /**
     * @var string
     */
    private $protocol;

    /**
     * @var string
     */
    private $query;

    /**
     * @var string
     */
    private $path;



    /**
     *
     * @param string|URL $url
     */
    public function __construct($url) {
        if ( $url != null ){
            if (is_string( $url )){

                $info = parse_url($url);
                $this->protocol = $info['scheme'] ? $info['scheme'] : 'http';
                $this->host     = $info['host'];
                $this->port     = $info['port'] ? (int)$info['port'] : 80;
                $this->path     = $info['path'] ? $info['path'] : '/';
                $this->query    = $info['query'];

                $this->url = $url;

            } else if ( $url instanceof URL ) {

                $this->protocol = $url->protocol;
                $this->host     = $url->host;
                $this->port     = $url->port;
                $this->path     = $url->path;
                $this->query    = $url->query;

                $this->url = $url;
            }
        }
    }

    public function getUrl(){
        return $this->url;
    }

    public function getPath(){
        return $this->path;
    }

    /**
     * @param URL $url
     * @return boolean
     */
    public function constaints(URL $url){
        return $this->port === $url->port
            && $this->protocol === $url->protocol
            && (!$url->host || $this->host === $url->host)
            && strpos( $this->path, $url->path ) === 0;
    }


    public static function build($host, $path, $query = '', $protocol = 'http', $port = 80){
        $url = new URL(null);
        $url->host = $host;
        $url->path = $path;
        $url->port = $port;
        $url->query = $query;
        $url->protocol = $protocol;

        $url->url = $protocol  . '://'
            . $host
            . ($port == 80 ? '' : $port)
            . $path
            . ($query ? '?' . $query : '');

        return $url;
    }

    public static function buildFromUri($host, $uri, $protocol = 'http', $port = 80){
        $tmp = explode('?', $uri, 2);
        return self::build( $host, $tmp[0], $tmp[1], $protocol, $port );
    }

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