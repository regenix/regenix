<?php
namespace framework\libs;

use framework\lang\CoreException;
use framework\lang\File;
use framework\lang\ArrayTyped;
use framework\lang\String;
use framework\mvc\Response;

abstract class WS {

    /**
     * @param $url
     * @return WSRequest
     */
    public static function url($url){
        return new WSRequest($url);
    }


    /**
     * Build HTTP Query
     *
     * @param array $params Name => value array of parameters
     * @return string HTTP query
     **/
    public static function buildHttpQuery(array $params){
        if (empty($params)) {
            return '';
        }

        $keys = self::urlencode(array_keys($params));
        $values = self::urlencode(array_values($params));
        $params = array_combine($keys, $values);

        uksort($params, 'strcmp');

        $pairs = array();
        foreach ($params as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }

        return implode('&', $pairs);
    }


    /**
     * URL Encode
     * @param string|array $item
     * @return mixed - array or string
     */
     public static function urlencode($item){

        static $search  = array('+', '%7E');
        static $replace = array('%20', '~');

        if (is_array($item))
            return array_map(array(__CLASS__, __FUNCTION__), $item);

        if (is_scalar($item) === false)
            return $item;

        return str_replace($search, $replace, rawurlencode($item));
    }

    /**
     * URL Decode
     * @param string|array $item
     * @return string|array Url decode string
     */
     public static function urldecode($item){
        if (is_array($item)) {
            return array_map(array(__CLASS__, __FUNCTION__), $item);
        }

        return rawurldecode($item);
    }
}


class WSRequest {

    /** @var string */
    protected $url;

    /** @var string */
    protected $encoding;

    /** @var string */
    protected $username;

    /** @var string */
    protected $password;

    /** @var mixed */
    protected $body;

    /** @var array */
    protected $fileParams;

    /** @var array */
    protected $headers = array();

    /** @var array */
    protected $parameters = array();

    /** @var string */
    protected $mimeType;

    /** @var callable */
    protected $progressCallback;

    /**
     * in seconds
     * @var int */
    protected $timeout = 60;

    /** @var bool */
    protected $followRedirects = true;

    public function __construct($url, $encoding = 'utf-8'){
        $this->encoding = $encoding;
        $this->url      = $url;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function contentType($type){
        $this->setHeader('Content-Type', $type);
        return $this;
    }

    /**
     * @param callable $func
     * @return $this
     */
    public function onProgress($func){
        $this->progressCallback = $func;
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function mimeType($value){
        $this->mimeType = $value;
        return $this;
    }

    public function authenticate($username, $password){
        $this->username = $username;
        $this->password = $password;
        return $this;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function followRedirects($value){
        $this->followRedirects = $value;
        return $this;
    }

    /**
     * @param string|int $value
     * @return $this
     */
    public function timeout($value){
        $this->timeout = Time::parseDuration($value);
        return $this;
    }

    /**
     * @param array $files
     * @return $this
     */
    public function files(array $files){
        $this->fileParams = $files;
        return $this;
    }

    /**
     * @param mixed $body
     * @return $this
     */
    public function body($body){
        $this->body = $body;
        return $this;
    }

    /**
     * @param mixed $body
     * @return $this
     */
    public function bodyJson($body){
        $this->contentType('application/json');
        return $this->body(json_encode($body));
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setHeader($name, $value){
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setParameter($name, $value){
        $this->parameters[$name] = $value;
        return $this;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function headers(array $headers){
        $this->headers = $headers;
        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function params(array $params){
        $this->parameters = $params;
        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setParameters(array $params){
        $this->parameters = array_merge($this->parameters, $params);
        return $this;
    }

    /**
     * @param string $method - POST, GET, PUT, OPTIONS, DELETE...
     * @return WSResponse
     * @throws
     */
    public function exec($method){
        $ch = curl_init($this->url);
        curl_setopt_array($ch, array(
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => $this->followRedirects,

            CURLOPT_VERBOSE        => false,
            CURLOPT_HEADER         => true,
            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_AUTOREFERER    => true,
            CURLOPT_MAXREDIRS      => 15,

            CURLOPT_FRESH_CONNECT => true,
        ));

        if ($this->progressCallback){
            $callback = $this->progressCallback;
            curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($ch, $dlTotal, $dlNow, $ulTotal, $ulNow) use ($callback){
                 call_user_func($callback, $ch, $dlTotal, $dlNow, $ulTotal, $ulNow);
            });
        }

        $headers = array();
        foreach($this->headers as $name => $value){
            $headers[] = $name . ': ' . $value;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        switch(strtolower($method)){
            case 'put':
            case 'post': {
                $data = array();
                foreach((array)$this->parameters as $name => $value){
                    $data[ $name ] = $value;
                }
                foreach((array)$this->fileParams as $name => $value){
                    $data[ $name ] = '@' . $value;
                }

                if ($this->body && sizeof($data)){
                    throw CoreException::formated('Can`t send body with post parameters');
                }

                if ($this->body)
                    $data = $this->body;

                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } break;

            case 'options':
            case 'delete':
            case 'head':
            case 'get': {
                $query = WS::buildHttpQuery($this->parameters);
                if ($query)
                    curl_setopt($ch, CURLOPT_URL, $this->url . '?' . $query);
            } break;
            default: {
                throw CoreException::formated('WS lib `%s` http method not support', $method);
            }
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        if (String::startsWith($this->url, 'https://')){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }

        $return = curl_exec($ch);
        $response = new WSResponse($ch, $return);

        curl_close($ch);
        return $response;
    }

    /**
     * @return WSResponse
     */
    public function get(){
        return $this->exec('get');
    }

    /**
     * @return WSResponse
     */
    public function post(){
        return $this->exec('post');
    }

    /**
     * @return WSResponse
     */
    public function put(){
        return $this->exec('put');
    }

    /**
     * @return WSResponse
     */
    public function delete(){
       return $this->exec('delete');
    }

    /**
     * @return WSResponse
     */
    public function options(){
        return $this->exec('options');
    }

    /**
     * @return WSResponse
     */
    public function head(){
        return $this->exec('head');
    }
}

class WSResponse {

    /** @var string */
    public $url;

    /** @var int */
    public $status;

    /** @var string */
    public $contentType;

    /** @var int */
    public $redirectCount;

    /** @var string */
    public $body;

    /** @var ArrayTyped */
    public $headers;

    public function __construct($ch, $body){
        $info = curl_getinfo($ch);

        $headers = array();
        $this->body = '';
        if ($body){
            $headerSize   = $info['header_size'];
            $headerString = substr($body, 0, $headerSize);
            foreach(explode("\n", $headerString) as $value){
                $tmp = explode(':', $value, 2);
                $key = strtolower($tmp[0]);
                $headers[$key] = $tmp[1];
            }
            $this->body    = substr($body, $headerSize);
        }
        $this->headers = new ArrayTyped($headers);

        $this->url    = $info['url'];
        $this->status = (int)$info['http_code'];
        $this->redirectCount = $info['redirect_count'];
        $this->contentType = $info['content_type'];
    }

    /**
     * @return bool
     */
    public function isSuccess(){
        return $this->status >= 200 && $this->status < 300;
    }

    /**
     * @throws
     * @return mixed
     */
    public function asJson(){
        $result = json_decode($this->body, true);
        $last = json_last_error();
        if ($last){
            throw CoreException::formated('Can`t parse JSON data');
        }
        return $result;
    }

    /**
     * @return array
     */
    public function asQuery(){
        $result = array();
        parse_str((string)$this->body, $result);
        return $result;
    }

    /**
     * @return string
     */
    public function asString(){
        return (string)$this->body;
    }

    /**
     * save body to file
     * @param File $file
     * @return int
     */
    public function asFile(File $file){
        $file->getParentFile()->mkdirs();
        return file_put_contents($file->getPath(), $this->body);
    }
}