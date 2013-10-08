<?php
namespace regenix\mvc\http;

use regenix\core\Regenix;
use regenix\lang\CoreException;
use regenix\lang\StrictObject;
use regenix\libs\Time;
use regenix\mvc\providers\ResponseProvider;

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
        $this->rawContent = null;
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

    /**
     * @param $contentType
     * @return $this
     */
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

    private $rawContent = null;

    /**
     * Get content that will output
     * @return null|string
     */
    public function getRawContent(){
        if ($this->rawContent !== null)
            return $this->rawContent;

        if (is_object($this->entity)){
            $providerClass = ResponseProvider::get(get_class($this->entity));
            $provider = new $providerClass($this);

            $provider->onBeforeRender();
            $content  = $provider->getContent();

            if ( $content === null ){
                ob_start();
                $provider->render();
                $content = ob_get_contents();
                ob_end_clean();
            }
        } else {
            $content = (string)$this->entity;
        }

        return $this->rawContent = $content;
    }

    /**
     * Set custom content as raw, do not use before outputting
     * @param $content
     * @throws \regenix\lang\CoreException
     */
    public function setRawContent($content){
        if ($this->rawContent === null)
            throw new CoreException("You cannot set a raw content before outputting");

        $this->rawContent = $content;
    }

    public function send($headers = true){
        $content = $this->getRawContent();
        if ( $headers )
            $this->sendHeaders();

        echo $content;
    }
}
