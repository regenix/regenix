<?php

namespace framework\mvc;

use framework\mvc\Response;
use framework\utils\StringUtils;

abstract class Controller {
    
    /** @var Response */
    protected $response;

    /** @var Request */
    protected $request;
    
    /** @var RequestURI */
    protected $uri;

    /** @var \framework\cache\AbstractCache */
    protected $cache;


    /** @var array */
    private $renderArgs = array();


    public function __construct() {
        $this->request  = Request::current();
        $this->response = new Response();
        $this->uri      = new RequestURI();
        $this->cache    = c('Cache');
        
        $this->onBefore();
    }

    public function onBefore(){}
    public function onAfter(){}
    public function onFinaly(){} 
    public function onException(\Exception $e){}
    
    /**
     * put a variable for template
     * @param string $varName
     * @param mixed $value
     * @return Controller
     */
    protected function put($varName, $value){
        $this->renderArgs[ $varName ] = $value;
        return $this;
    }
    
    protected function has($varName){
        return isset($this->renderArgs[$varName]);
    }

        /**
     * put a variables for template
     * @param array $vars
     * @return Controller
     */
    protected function putAll(array $vars){
        foreach ($vars as $key => $value) {
            $this->put($key, $value);
        }
        return $this;
    }
    
    protected function send(){
        
        throw new results\Result($this->response);
    }

    protected function redirect($url, $permanent = false){
        
        $this->response
                ->setStatus($permanent ? 301 : 302)
                ->setHeader('Location', $url);
        
        $this->send();
    }

    /**
     * render template by action method name or template name
     * @param [string $template] default controller action method
     * @param [array $args] add vars to template
     */
    protected function render($template = false, array $args = null){
        
        if ( $template === false ) {
            $trace      = debug_backtrace();
            $current    = $trace[1];
            $controller = str_replace('\\', '/', $current['class']);
            
            if ( StringUtils::startsWith($controller, 'controllers/') )
                $controller = substr($controller, 12);    
            
            $template   = $controller . '/' . $current['function'];
        }
        
        $this->renderTemplate($template, $args);
    }
    
    protected function renderTemplate($template, array $args = null){
        
        if ( $args )
            $this->putAll($args);
        
        $template = template\TemplateLoader::load($template);
        $template->putArgs( $this->renderArgs );
        
        $this->response->setEntity($template);
        $this->send();
    }


    protected function renderText($text){
        
        $this->response->setEntity( $text );
        $this->send();
    }
    
    protected function renderHTML($html){
        
        $this->response
                ->setContentType(\framework\utils\MIMETypes::getByExt('html'))
                ->setEntity($html);
        
        $this->send();
    }

    protected function renderJSON($object){
        
        $this->response
                ->setContentType( \framework\utils\MIMETypes::getByExt('json') )
                ->setEntity( json_encode($object) );
        
        $error = json_last_error();
        if ( $error > 0 ){
            throw new \framework\exceptions\CoreException('Error json encode, ' . $error);
        }
        
        $this->send();
    }
    
    protected function renderXML($xml){
        
        if ( $xml instanceof \SimpleXMLElement ){
            /** @var \SimpleXMLElement */
           
        }
    }
}