<?php

namespace framework\net;

class URL {

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
     * @param \framework\net\URL $url
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
