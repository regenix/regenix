<?php
namespace regenix\libs\ws;

use regenix\lang\CoreException;
use regenix\lang\String;
use regenix\lang\types\Callback;
use regenix\libs\Time;

class WSRequest {

    /** @var bool */
    protected $persistent = false;

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

    /** @var Callback */
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
     * @param $persistent
     * @return $this
     */
    public function persistent($persistent){
        $this->persistent = $persistent;
        return $this;
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
     * @param Callback $func
     * @return $this
     */
    public function onProgress(Callback $func){
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
     * @param int $options
     * @return $this
     */
    public function bodyJson($body, $options = 0){
        $this->contentType('application/json');
        return $this->body(json_encode($body, $options));
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
     * @throws CoreException
     * @return WSResponse
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
            CURLOPT_FORBID_REUSE  => $this->persistent
        ));


        if ($this->progressCallback){
            $callback = $this->progressCallback;
            curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($ch, $dlTotal, $dlNow, $ulTotal, $ulNow) use ($callback){
                $callback->invoke($ch, $dlTotal, $dlNow, $ulTotal, $ulNow);
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
                    throw new CoreException('Can`t send body with post parameters');
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
            throw new CoreException('WS lib `%s` http method not support', $method);
            }
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        if (String::startsWith($this->url, 'https://')){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        $return = curl_exec($ch);
        $err = curl_errno($ch);
        if ($err){
            throw new CoreException('Curl exec error - %s: %s' , $err, curl_error($ch));
        }

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
