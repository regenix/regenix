<?php

namespace regenix\mvc;

use regenix\Regenix;
use regenix\lang\File;
use regenix\lang\StrictObject;
use regenix\lang\CoreException;
use regenix\lang\ArrayTyped;
use regenix\lang\String;
use regenix\libs\Time;
use regenix\mvc\providers\ResponseProvider;

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
 * @package regenix\mvc
 */
class Session extends StrictObject {

    private $init = false;
    private $id;

    protected function __construct(){
    }

    protected function check(){
        if (!$this->init){
            if (Regenix::isCLI()){
                global $_SESSION;
                $_SESSION = array();
                $this->id = String::random(40);
            } else {
                $this->id = session_id();
                if (!$this->id)
                    session_start();
            }

            $this->init = true;
        }
    }

    public function isInit(){
        return $this->init;
    }

    /**
     * @return string
     */
    public function getId(){
        $this->check();
        return Regenix::isCLI() ? $this->id : session_id();
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
        global $_SESSION;
        $this->check();
        return $this->has($name) ? $_SESSION[$name] : $def;
    }

    /**
     * @param $name string
     * @param $value string|int|float|null
     */
    public function put($name, $value){
        global $_SESSION;
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
        global $_SESSION;
        return isset($_SESSION[$name]);
    }

    /**
     * @param string $name
     */
    public function remove($name){
        $this->check();
        global $_SESSION;
        unset($_SESSION[$name]);
    }

    /**
     * clear all session values
     */
    public function clear(){
        $this->check();
        if (Regenix::isCLI()){
            global $_SESSION;
            $_SESSION = array();
        } else
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

abstract class SessionDriver {

    abstract public function open($savePath, $sessionName);
    abstract public function close();
    abstract public function read($id);
    abstract public function write($id, $value);
    abstract public function destroy($id);
    abstract public function gc($lifetime);

    public function register(){
        session_set_save_handler(
            array($this, 'open'), array($this, 'close'),
            array($this, 'read'), array($this, 'write'),
            array($this, 'destroy'), array($this, 'gc')
        );
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
        setcookie($name, $value, $expires ? Time::parseDuration($expires) : $expires, '/');
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


    public function __construct($query = null) {
        $this->request = Request::current();
        $this->args = URL::parseQuery( $query !== null ? $query : $this->request->getQuery() );
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
     * @throws BindValueException
     * @throws BindValueInstanceException
     * @return array|bool|float|\regenix\mvc\RequestBindValue|int|string
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

    const type = __CLASS__;

    public function __construct($value, $type){
        parent::__construct(String::format('Can\'t bind `%s` value as `%s` type', (string)$value, $type));
    }
}

class BindValueInstanceException extends CoreException {

    const type = __CLASS__;

    public function __construct($type){
        parent::__construct(String::format(
            'Bind error: `%s` class must be implements \regenix\mvc\RequestBindValue interface for bind value', $type
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

class UploadFile extends File {

    protected $meta;
    protected $uploadName;

    /** @var File */
    protected $uploadFile;

    public function __construct($uploadName, $meta){
        $this->uploadName = $uploadName;
        $this->meta       = $meta;

        parent::__construct($meta['tmp_name']);
    }

    /**
     * @param $uploadUrl
     * @return \regenix\lang\File
     * @return File
     */
    public static function buildFromUrl($uploadUrl){
        $file = new File(ROOT . $uploadUrl);
        return $file->isFile() ? $file : null;
    }

    /**
     * @param $uploadUrl
     */
    public static function deleteFromUrl($uploadUrl){
        $tmp = static::buildFromUrl($uploadUrl);
        if ($tmp)
            $tmp->delete();
    }

    /**
     * get upload mime type
     * @return string
     */
    public function getMimeType(){
        return $this->meta['type'];
    }

    /**
     * get extension by mime type
     * @return string
     */
    public function getMimeExtension(){
        return MIMETypes::getByMimeType($this->getMimeType());
    }

    /**
     * Get user upload file name
     * @param null $suffix
     * @return mixed
     */
    public function getUserName($suffix = null){
        return basename($this->meta['name'], $suffix);
    }

    /**
     * @return mixed
     */
    public function getUserNameWithoutExtension(){
        return $this->getUserName('.' . $this->getUserExtension());
    }

    /**
     * Get real extension
     * @return string
     */
    public function getUserExtension(){
        $tmp = new File($this->getUserName());
        return $tmp->getExtension();
    }

    /**
     * @return int
     */
    public function length(){
        return $this->meta['size'];
    }

    /**
     * Get uploaded file, after call doUpload...
     * @return File
     */
    public function getUploadedFile(){
        return $this->uploadFile;
    }

    /**
     * @return string
     */
    public function getUploadedPath(){
        return $this->uploadFile ? $this->uploadFile->getPath() : '';
    }

    /**
     * Get url of uploaded file
     * @return string|null
     */
    public function getUploadedUrl(){
        if ($this->uploadFile){
            return static::convertToPathToUrl($this->uploadFile->getPath());
        } else {
            return null;
        }
    }

    public static function convertToPathToUrl($path){
        $src = str_replace(array('//', '///', '////', '/////'), '/', $path);
        return str_replace(ROOT, '/', $src);
    }

    /**
     * Move upload file to new filename
     * @param $filename
     * @return bool
     */
    protected function moveTo($filename){
        return move_uploaded_file($this->getPath(), $filename);
    }

    /**
     * @param string $prefix
     * @param null|string $uploadPath
     * @return bool
     * @throws \regenix\lang\CoreException
     */
    public function doUpload($prefix = '', $uploadPath = null){
        $uploadPath = $uploadPath ? $uploadPath : Regenix::app()->getPublicPath();

        $ext = $this->getMimeExtension();
        if (!$ext)
            $ext = $this->getUserExtension();

        $filename = File::sanitize($this->getUserNameWithoutExtension())
            . md5($_SERVER["REMOTE_ADDR"] . $this->getUserName() . time())
            . ($ext ? '.' . $ext : '');

        $fullPath = new File($uploadPath . $prefix);
        if (!$fullPath->isDirectory()){
            if (!$fullPath->mkdirs()){
                throw new CoreException('Can`t create upload dir for "%s" prefix', $prefix);
            }
        }

        $this->uploadFile = new File($fullPath->getPath() . '/' . $filename);
        return $this->moveTo($this->uploadFile->getPath());
    }

    /**
     * @param $fileName
     * @param string $prefix
     * @param null $uploadPath
     * @return bool
     * @throws \regenix\lang\CoreException
     */
    public function doUploadToFile($fileName, $prefix = '', $uploadPath = null){
        $uploadPath = $uploadPath ? $uploadPath : Regenix::app()->getPublicPath();

        $ext = $this->getMimeExtension();
        if (!$ext)
            $ext = $this->getUserExtension();

        $fullPath = new File($uploadPath . $prefix);
        if (!$fullPath->isDirectory()){
            if (!$fullPath->mkdirs()){
                throw new CoreException('Can`t create upload dir for "%s" prefix', $prefix);
            }
        }

        $this->uploadFile = new File($fullPath->getPath() . '/' . $fileName);
        return $this->moveTo($fullPath->getPath() . '/' . $fileName);
    }

    /**
     *
     */
    public function deleteUploaded(){
        $file = $this->getUploadedFile();
        if ($file)
            $file->delete();
    }
}

class RequestBody extends StrictObject {

    const type = __CLASS__;

    private $data = null;

    public function __construct(){
        ;
    }

    protected function getData(){
        if ($this->data)
            return $this->data;

        return $this->data = file_get_contents('php://input');
    }

    /**
     * @param $name
     * @return UploadFile
     */
    public function getFile($name){
        $meta = $_FILES[$name];

        if ($meta){
            return new UploadFile($name, $meta);
        } else
            return null;
    }

    /**
     * @param string $prefix
     * @return array
     * @return UploadFile[]
     */
    public function getFiles($prefix){
        $meta = $_FILES[$prefix];
        if (!is_array($meta))
            $meta = array($meta);

        $result = array();
        foreach($meta as $el){
            $result[] = new UploadFile($prefix, $el);
        }
        return $result;
    }

    /**
     * @return UploadFile[string]
     */
    public function getAllFiles(){
        $result = array();
        foreach($_FILES as $name => $meta){
            if (is_array($meta)){
                foreach($result as $el){
                    $result[$name][] = new UploadFile($name, $el);
                }
            } else
                $result[$name] = new UploadFile($name, $meta);
        }
        return $result;
    }

    /**
     * parse data as json
     * @return array
     */
    public function asJson(){
        $json = json_decode($this->getData(), true);
        return $json;
    }

    /**
     * parse data as query string
     * @return ArrayTyped
     */
    public function asQuery(){
        return new ArrayTyped($_POST);
    }

    /**
     * @return array
     */
    public function asArray(){
        return $_POST;
    }

    /**
     * get data as string
     * @return string
     */
    public function asString(){
        return (string)$this->getData();
    }

    /**
     * @param $object
     * @param string $prefix
     * @param array $fields
     * @param string $method - query or json
     * @throws \regenix\lang\CoreException
     * @throws \InvalidArgumentException
     * @return object
     */
    public function appendTo(&$object, $fields = array(), $prefix = '', $method = 'query'){
        $data = null;
        switch($method){
            case 'query': $data = $this->asQuery(); break;
            case 'json' : $data = $this->asJson(); break;
            default: {
                throw new CoreException('Unknown method `%s` for appendTo', $method);
            }
        }

        if (is_object($object)) {
            $class = new \ReflectionClass($object);
            foreach($data as $name => $value){
                if ($fields && !in_array($name, $fields, true)) continue;
                if ($prefix && !String::startsWith($name, $prefix)) continue;

                $property = $prefix ? substr($name, strlen($prefix)) : $name;
                if ($class->hasProperty($property)){
                    $prop = $class->getProperty($property);
                    if ($prop->isPublic() && !$prop->isStatic()){
                        $prop->setValue($object, $value);
                    }
                }
            }
        } else
            throw new \InvalidArgumentException('Argument 1 must be object');

        return $object;
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

                $this->url = $url->url;
            }
        }
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param string $protocol
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
    }

    /**
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @param string $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
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
    public function constraints(URL $url){
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
            . ($port == 80 ? '' : ':' . $port)
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

    /**
     * Function: sanitize
     * Returns a sanitized string, typically for URLs.
     *
     * Parameters:
     *     $string - The string to sanitize.
     *     $force_lowercase - Force the string to lowercase?
     *     $anal - If set to *true*, will remove all non-alphanumeric characters.
     */
    public static function sanitize($string, $forceLowercase = true, $anal = false) {
        $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
            "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
            "â€”", "â€“", ",", "<", ".", ">", "/", "?");
        $clean = trim(str_replace($strip, "", strip_tags($string)));
        $clean = preg_replace('/\s+/', "-", $clean);
        $clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;
        return ($forceLowercase) ?
            (function_exists('mb_strtolower')) ?
                mb_strtolower($clean, 'UTF-8') :
                strtolower($clean) :
            $clean;
    }
}



class Response extends StrictObject {

    const type = __CLASS__;

    private $status;
    private $contentType;
    private $entity;
    private $httpVersion;
    private $charset;

    private $headers = array();

    public function __construct() {
        $this
            ->setStatus( 200 )
            ->setContentType( 'text/plain' )
            ->setHttpVersion( '1.1' )
            ->setCharset( 'UTF-8' )
            ->setEntity(null);
    }

    public function setEntity($object) {
        $this->entity = $object;
        return $this;
    }

    public function getEntity(){
        return $this->entity;
    }

    public function setCharset($charset){
        $this->charset = $charset;
        return $this;
    }

    public function setStatus($status){
        $this->status = (int)$status;
        return $this;
    }

    public function setHttpVersion($version){
        $this->httpVersion = $version;
        return $this;
    }

    public function setContentType($contentType){
        $this->contentType = $contentType;
        return $this;
    }

    public function setHeader($name, $value){
        $this->headers[$name] = $value;
        return $this;
    }

    public function setHeaders(array $headers){
        $this->headers = $headers;
        return $this;
    }

    public function applyHeaders(array $headers){
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function cacheFor($duration){
        $maxAge = Time::parseDuration($duration);
        $this->setHeader("Cache-Control", "max-age=" . $maxAge);
        return $this;
    }

    public function cacheETag($etag, $lastModified = null){
        $this->setHeader("Last-Modified", gmdate("D, d M Y H:i:s ", $lastModified));
        $this->setHeader("Etag", $etag);
        return $this;
    }

    public function cacheForETag($etag, $duration = '999m', $lastModified = null){
        $this->cacheFor($duration);
        return $this->cacheETag($etag, $lastModified);
    }

    public function sendHeaders(){
        header('HTTP/' . $this->httpVersion . ' ' . (int)$this->status, true);
        header('Content-type: '. $this->contentType . '; charset=' . $this->charset, true);

        foreach($this->headers as $name => $value){
            header($name . ': ' . $value, true);
        }
        header('Powered-By: Regenix Framework v' . Regenix::getVersion(), true);
    }

    public function send($headers = true){
        if ( is_object($this->entity) ){
            $providerClass = ResponseProvider::get(get_class($this->entity));
            $provider = new $providerClass($this);
            if ( $headers )
                $this->sendHeaders();

            $provider->onBeforeRender();
            $content  = $provider->getContent();

            if ( $content === null ){
                $provider->render();
            }

        } else {
            if ( $headers )
                $this->sendHeaders();
            $content = (string)$this->entity;
        }

        if ( $content != null ){
            echo $content;
        }
    }
}

abstract class MIMETypes {

    const type = __CLASS__;

    /**
     * @var array
     */
    protected static $exts = array(

        // applications
        'json'      => 'application/json',
        'js'        => 'application/javascript',
        'pdf'       => 'application/pdf',
        'zip'       => 'application/zip',
        'rar'       => 'application/x-tar',
        'gzip'      => 'application/x-gzip',
        'gz'        => 'application/x-gzip',
        'torrent'   => 'application/x-bittorrent',

        'doc'       => 'application/msword',
        'docx'      => 'application/msword',
        'rtf'       => 'application/msword',

        // audio
        'mp4'       => 'audio/mp4',
        'wav'       => 'audio/x-wav',
        'wave'      => 'audio/x-wav',
        'ogg'       => 'audio/ogg',
        'mp3'       => 'audio/mpeg',

        // video
        'avi'       => 'video/avi',
        'mpeg'      => 'video/mpeg',
        'mpg'       => 'video/mpeg',
        'mpe'       => 'video/mpeg',
        'mov'       => 'video/quicktime',
        'qt'        => 'video/quicktime',

        // images
        'bmp'       => 'image/bmp',
        'jpg'       => 'image/jpeg',
        'jpeg'      => 'image/jpeg',
        'gif'       => 'image/gif',
        'png'       => 'image/png',
        'swf'       => 'application/futuresplash',
        'tiff'      => 'image/tiff',

        'html'      => 'text/html',
        'htm'       => 'text/html',
        'phtml'     => 'text/html',
        'xml'       => 'text/xml',
        'css'       => 'text/css',
        'txt'       => 'text/plain',

        'exe'       => 'application/x-msdownload'
    );


    /**
     * get mime type from file extension
     * @param string $ext file extension without dot
     * @return string
     */
    public static function getByExt($ext){
        if (strpos( $ext, '.' ) === 0){
            $ext = substr($ext, 1);
        }

        return self::$exts[strtolower( $ext )];
    }

    /**
     * get extension by mimetype
     * @param $mimeType
     * @return string
     */
    public static function getByMimeType($mimeType){
        return array_search(strtolower($mimeType), self::$exts);
    }

    /**
     * register new mime type for file extension
     * @param mixed $ext file extension(s)
     * @param string $mime mime type
     */
    public static function registerExtension($ext, $mime){
        self::$exts[ $ext ] = $mime;
    }
}