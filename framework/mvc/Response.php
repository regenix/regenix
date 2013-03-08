<?php

namespace framework\mvc;

use framework\mvc\providers\ResponseProvider;

class Response {

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

    public function sendHeaders(){
        
        
        header('HTTP/' . $this->httpVersion . ' ' . (int)$this->status);
        header('Content-type: '. $this->contentType . '; charset=' . $this->charset);
        
        foreach($this->headers as $name => $value){
            header($name . ': ' . $value);
        }
    }

    public function send($headers = true){
        
        if ( is_object($this->entity) ){
            
            $providerClass = ResponseProvider::get(get_class($this->entity)); 
            
            if ( $headers )
                $this->sendHeaders();
            
            $provider = new $providerClass($this);
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

