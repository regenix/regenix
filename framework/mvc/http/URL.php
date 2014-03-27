<?php
namespace regenix\mvc\http;

use regenix\lang\StrictObject;

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
    public function setQuery($query) {
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

    /**
     * @return string
     */
    public function getAddress() {
        $s = $this->protocol . '://' . $this->host;
        if ($this->port == 80)
            return $s;
        else
            return $s . ':' . $this->port;
    }

    /**
     * @return string
     */
    public function getRelativeUrl() {
        return str_replace($this->getAddress(), '', $this->url);
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

    /**
     * @param string $host
     * @param string $path
     * @param string $query
     * @param string $protocol
     * @param int $port
     * @return URL
     */
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

    /**
     * @param string $host
     * @param string $uri
     * @param string $protocol
     * @param int $port
     * @return URL
     */
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