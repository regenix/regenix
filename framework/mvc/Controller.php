<?php

namespace framework\mvc;

use framework\mvc\Response;
use framework\mvc\template\TemplateLoader;
use framework\utils\StringUtils;

abstract class Controller {

    /** @var Response */
    public $response;

    /** @var Request */
    public $request;
    
    /** @var RequestURI */
    public $uri;

    /** @var \framework\cache\AbstractCache */
    public $cache;


    /** @var array */
    private $renderArgs = array();


    public function __construct() {
        $this->request  = Request::current();
        $this->response = new Response();
        $this->uri      = new RequestURI();
        $this->cache    = c('Cache');
        
        $this->onBefore();
    }

    protected function onBefore(){}
    protected function onAfter(){}
    protected function onFinaly(){} 
    protected function onException(\Exception $e){}
    
    public function callBefore(){
        $this->onBefore();
    }
    
    public function callAfter(){
        $this->onAfter();
    }
    
    final public function callFinaly(){
        $this->onFinaly();
    }
    
    final public function callException(\Exception $e){
        $this->onException($e);
    }

    /**
     * put a variable for template
     * @param string $varName
     * @param mixed $value
     * @return Controller
     */
    public function put($varName, $value){
        $this->renderArgs[ $varName ] = $value;
        return $this;
    }
    
    public function has($varName){
        return isset($this->renderArgs[$varName]);
    }

    /**
     * put a variables for template
     * @param array $vars
     * @return Controller
     */
    public function putAll(array $vars){
        foreach ($vars as $key => $value) {
            $this->put($key, $value);
        }
        return $this;
    }
    
    protected function send(){
        
        throw new results\Result($this->response);
    }

    public function redirect($url, $permanent = false){
        
        $this->response
                ->setStatus($permanent ? 301 : 302)
                ->setHeader('Location', $url);
        
        $this->send();
    }

    /**
     * switch template engine
     * @param string $templateEngine
     */
    public function setTemplateEngine($templateEngine){
        TemplateLoader::switchEngine($templateEngine);
    }

     /**
     * render template by action method name or template name
     * @param [string $template] default controller action method
     * @param [array $args] add vars to template
     */
    public function render($template = false, array $args = null){
        
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
    
    public function renderTemplate($template, array $args = null){
        
        if ( $args )
            $this->putAll($args);
        
        $template = template\TemplateLoader::load($template);
        $template->putArgs( $this->renderArgs );
        
        $this->response->setEntity($template);
        $this->send();
    }


    public function renderText($text){
        
        $this->response->setEntity( $text );
        $this->send();
    }
    
    public function renderHTML($html){
        
        $this->response
                ->setContentType(\framework\utils\MIMETypes::getByExt('html'))
                ->setEntity($html);
        
        $this->send();
    }

    public function renderJSON($object){
        
        $this->response
                ->setContentType( \framework\utils\MIMETypes::getByExt('json') )
                ->setEntity( json_encode($object) );
        
        $error = json_last_error();
        if ( $error > 0 ){
            throw new \framework\exceptions\CoreException('Error json encode, ' . $error);
        }
        
        $this->send();
    }
    
    public function renderXML($xml){
        
        if ( $xml instanceof \SimpleXMLElement ){
            /** @var \SimpleXMLElement */
           
        }
    }
}