<?php

namespace framework\mvc;

use framework\net\URL;

class Request {

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

    public function __construct() {
        // TODO
    }
    
    public static function createFromGlobal(){
        
        $req = new Request();
        $req->setMethod($_SERVER['METHOD']);
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