<?php
namespace regenix\libs\ws;

use regenix\exceptions\JsonParseException;
use regenix\lang\ArrayTyped;
use regenix\lang\CoreException;
use regenix\lang\File;

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
        return $this->status >= 200 && $this->status < 400;
    }

    /**
     * @throws \regenix\exceptions\JsonParseException
     * @return mixed
     */
    public function asJson(){
        $result = json_decode($this->body, true);
        if (json_last_error()){
            throw new JsonParseException();
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
