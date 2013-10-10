<?php

namespace regenix\mvc\http;

use regenix\core\Regenix;
use regenix\lang\DI;
use regenix\lang\IClassInitialization;
use regenix\lang\StrictObject;
use regenix\lang\String;
use regenix\mvc\route\RouteInjectable;

class Request extends StrictObject
    implements RouteInjectable, IClassInitialization {

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

    public static function createFromGlobal($headers = null){
        if ($headers === null)
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
        $headers = array_change_key_case((array)$headers, CASE_LOWER);

        $req = new Request($headers);
        $req->setMethod($_SERVER['REQUEST_METHOD']);
        $req->setUri($_SERVER['REQUEST_URI']);
        $req->userAgent = $_SERVER['HTTP_USER_AGENT'];
        $req->referer   = $_SERVER['HTTP_REFERER'];
        $req->protocol  = $_SERVER['HTTPS'] ? 'https' : 'http';

        $host = explode(':', $_SERVER['HTTP_HOST']);
        $req->host = $host[0];
        $req->port = $host[1] ? (int)$host[1] : ($_SERVER['HTTPS'] ? 443 : 80);

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
            $langs[] = trim($lang);
        }
        return $langs;
    }

    /**
     * @param string $method - get, post, etc.
     * @return \regenix\mvc\Request
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
     * @param int $port
     */
    public function setPort($port){
        $this->port = $port;
        $this->currentUrl->setPort($port);
    }

    /**
     * @return boolean
     */
    public function isPost(){
        return $this->isMethod('POST');
    }

    /**
     * @return bool
     */
    public function isGet(){
        return $this->isMethod('GET');
    }

    /**
     * @param string $method - post, get, put, delete, etc.
     * @return boolean
     */
    public function isMethod($method){
        return $this->method === strtoupper($method);
    }

    /**
     *
     * @param array $methods
     * @return boolean
     */
    public function isMethods(array $methods){
        foreach ($methods as $value) {
            if ( strtoupper($value) === $this->method )
                return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isAjax(){
        return strtolower($this->getHeader('x-requested-with')) === 'xmlhttprequest';
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
     * @param $baseUrl
     * @internal param \regenix\mvc\URL|string $url
     * @return boolean
     */
    public function isBase($baseUrl){
        if ( !($baseUrl instanceof URL) ){
            $baseUrl = new URL($baseUrl);
        }
        return $this->currentUrl->constraints($baseUrl);
    }

    /**
     * @param string $domain
     * @param bool $cutWWW
     * @return bool
     */
    public function isDomain($domain, $cutWWW = true){
        $host = $this->currentUrl->getHost();
        if ($cutWWW){
            if (String::startsWith($host, 'www.'))
                $host = substr($host, 4);

            if (String::startsWith($domain, 'www.'))
                $domain = substr($domain, 4);
        }
        return (strtolower($host) === strtolower($domain));
    }

    public static function initialize() {
        DI::bindTo(Request::type, function(){
            // get current
            return Request::createFromGlobal();
        }, true);
    }
}
