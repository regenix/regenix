<?php
namespace regenix\test;

use regenix\lang\CoreException;
use regenix\libs\ws\WS;
use regenix\libs\ws\WSResponse;
use regenix\mvc\http\URL;

class FunctionalTest extends UnitTest {

    private $lastMethod;

    private $lastData;

    private $lastUrl;

    /** @var string */
    private $baseUrl;

    /** @var WSResponse */
    private $response;

    /** @var array */
    private $headers = array();

    protected function setBaseUrl($url){
        $url = new URL($url);
        $this->baseUrl = $url->getProtocol() . '://' . $url->getHost() . ':' . $url->getPort() . $url->getPath();
        if ($query = $url->getQuery())
            $this->baseUrl .= '?' . $query;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    protected function setHeader($name, $value){
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * @return $this
     */
    protected function clearHeaders(){
        $this->headers = array();
        return $this;
    }

    protected function RETRY(){
        switch($this->lastMethod){
            case 'POST': return $this->POST($this->lastUrl, $this->lastData);
            case 'POST_JSON': return $this->POST_JSON($this->lastUrl, $this->lastData);
            case 'GET': return $this->GET($this->lastUrl);
            default: {
                throw new CoreException('Cannot retry empty request, requests are not occur yet');
            }
        }
    }

    /**
     * @param $path
     * @param array $data
     * @return WSResponse
     */
    protected function POST($path, array $data = array()){
        $this->lastMethod = 'POST';
        $this->lastUrl = $path;
        $this->lastData = $data;
        return $this->response = WS::url($this->baseUrl . $path)->headers($this->headers)->params($data)->post();
    }

    /**
     * @param $path
     * @param $data
     * @return WSResponse
     */
    protected function POST_JSON($path, $data){
        $this->lastMethod = 'POST_JSON';
        $this->lastUrl = $path;
        $this->lastData = $data;
        return $this->response = WS::url($this->baseUrl . $path)
            ->headers($this->headers)
            ->bodyJson($data, JSON_NUMERIC_CHECK)
            ->post();
    }

    /**
     * @param $path
     * @return WSResponse
     */
    protected function GET($path){
        $this->lastMethod = 'GET';
        $this->lastUrl = $path;
        $this->lastData = null;

        return $this->response = WS::url($this->baseUrl . $path)->headers($this->headers)->get();
    }

    /**
     * @return WSResponse
     */
    protected function getResponse(){
        return $this->response;
    }

    /**
     * @param $content
     * @param WSResponse $response
     * @return $this
     */
    protected function assertContent($content, WSResponse $response = null){
        $response = $response ? $response : $this->response;
        $this->assertWrite($response && $content === $response->asString(), 'Check content');
        return $this;
    }

    /**
     * @param array $json
     * @param WSResponse $response
     * @return $this
     * @throws \regenix\lang\CoreException
     */
    protected function assertJsonContent($json = array(), WSResponse $response = null){
        $response = $response ? $response : $this->response;
        try {
            if (!$response)
                throw new CoreException('');

            $result = $response->asJson();
            foreach($json as $key => $value){
                if ($value !== gettype($result[$key]))
                    $this->assertWrite(false, 'Check json content');
            }

            $this->assertWrite(true, 'Check json content');
        } catch (CoreException $e){
            $this->assertWrite(false, 'Check json content');
        }
        return $this;
    }

    /**
     * @param $contentType
     * @param WSResponse $response
     * @return $this
     */
    protected function assertContentType($contentType, WSResponse $response = null){
        $response = $response ? $response : $this->response;
        $this->assertWrite($response && $response->contentType === $contentType, 'Check content type');
        return $this;
    }

    /**
     * @param $status
     * @param WSResponse $response
     * @return $this
     */
    protected function assertStatus($status, WSResponse $response = null){
        $response = $response ? $response : $this->response;
        $this->assertWrite($response && (int)$status === $response->status, 'Check http status = ' . (int)$status);
        return $this;
    }

    /**
     * @param WSResponse $response
     * @return $this
     */
    protected function assertSuccess(WSResponse $response = null){
        $response = $response ? $response : $this->response;
        $this->assertWrite($response && $response->isSuccess(), 'Check http success code = 2xx');
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @param WSResponse $response
     * @return $this
     */
    protected function assertHeader($key, $value, WSResponse $response = null){
        $key = strtolower($key);
        $response = $response ? $response : $this->response;
        $this->assertWrite($response && $response->headers->get($key) == $value, 'Check http header value');
        return $this;
    }

    /**
     * @param $url
     * @param WSResponse $response
     * @return $this
     */
    protected function assertUrl($url, WSResponse $response = null){
        $response = $response ? $response : $this->response;
        $this->assertWrite($response && $response->url === $url, 'Check result url');
        return $this;
    }

    /**
     * @param WSResponse $response
     * @return $this
     */
    protected function assertRedirect(WSResponse $response = null){
        $response = $response ? $response : $this->response;
        $this->assertWrite($response && $response->redirectCount > 0, 'Check redirect exists');
        return $this;
    }
}