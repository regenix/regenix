<?php

namespace framework\mvc;

use framework\Core;
use framework\StrictObject;
use framework\exceptions\CoreException;
use framework\exceptions\NotFoundException;
use framework\io\File;
use framework\mvc\Response;
use framework\mvc\template\TemplateLoader;
use framework\lang\String;
use framework\mvc\MIMETypes;

abstract class Controller extends StrictObject {

    const type = __CLASS__;

    private $__data = array(
        'request' => null,
        'session' => null,
        'flash'   => null,
        'cookie'  => null,
        'query'   => null,
        'body'    => null
    );

    /** @var Response */
    public $response;

    /** @var Request */
    public $request;

    /** @var Session */
    public $session;

    /** @var Flash */
    public $flash;

    /** @var Cookie */
    public $cookie;
    
    /** @var RequestQuery */
    public $query;

    /** @var RequestBody */
    public $body;

    /**
     * method name of invoke in request
     * @var string
     */
    public $actionMethod;

    /**
     * template arguments
     * @var array[string, any]
     **/
    private $renderArgs = array();

    /**
     * get current route arguments
     * @var array[string, any]
     */
    public $routeArgs = array();

    /**
     * @var Controller
     */
    private static $current;

    public function __construct() {
        self::$current  = $this;
        $this->response = new Response();

        unset($this->body);
        unset($this->request);
        unset($this->cookie);
        unset($this->session);
        unset($this->flash);
        unset($this->query);
    }

    public function __get($name){
        if ($this->__data[$name])
            return $this->__data[$name];

        $value = null;
        switch($name){
            case 'body': $value = new RequestBody(); break;
            case 'request': $value = Request::current(); break;
            case 'cookie': $value = Cookie::current(); break;
            case 'session': $value = Session::current(); break;
            case 'flash': $value = Flash::current(); break;
            case 'query': $value = new RequestQuery(); break;
            default: {
                return parent::__get($name);
            }
        }

        return $this->__data[$name] = $value;
    }

    protected function onBefore(){}
    protected function onAfter(){}
    protected function onFinally(){}
    protected function onException(\Exception $e){}
    
    public function callBefore(){
        $this->onBefore();
    }
    
    public function callAfter(){
        $this->onAfter();
    }
    
    final public function callFinally(){
        $this->onFinally();
        $this->flash->touchAll();
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

    public function send(){
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
     * Work out the default template to load for the invoked action.
     * E.g. "controllers\Pages\index" returns "views/Pages/index.html".
     */
    public function template(){
        $controller = str_replace('\\', '/', get_class($this));

        if ( String::startsWith($controller, 'controllers/') )
            $controller = substr($controller, 12);

        $template   = $controller . '/' . $this->actionMethod;
        return $template;
    }

    /**
     * Render the corresponding template
     * render template by action method name or template name
     * @param bool $template
     * @param array $args
     */
    public function render($template = false, array $args = null){
        $this->renderTemplate($template === false ? $this->template() : $template, $args);
    }

    /**
     * Render a specific template.
     * @param $template
     * @param array $args
     */
    public function renderTemplate($template, array $args = null){
        if ( $args )
            $this->putAll($args);

        $this->put("flash", $this->flash);
        $this->put("session", $this->session);
        $this->put("request", $this->request);
        
        $template = template\TemplateLoader::load($template);
        $template->putArgs( $this->renderArgs );
        
        $this->response->setEntity($template);
        $this->send();
    }

    /**
     * @param $template string
     * @return bool
     */
    public function templateExists($template){
        $template = TemplateLoader::load($template);
        return !!$template;
    }

    public function renderText($text){
        $this->response->setEntity( $text );
        $this->send();
    }
    
    public function renderHTML($html){
        $this->response
                ->setContentType(MIMETypes::getByExt('html'))
                ->setEntity($html);
        
        $this->send();
    }

    public function renderJSON($object){
        $this->response
                ->setContentType(MIMETypes::getByExt('json'))
                ->setEntity( json_encode($object) );
        
        $error = json_last_error();
        if ( $error > 0 ){
            throw new CoreException('Error json encode, ' . $error);
        }
        
        $this->send();
    }
    
    public function renderXML($xml){
        if ( $xml instanceof \SimpleXMLElement ){
            /** @var \SimpleXMLElement */
            /// TODO
        }
    }

    public function renderFile(File $file){
        $this->response->setEntity($file);
        $this->send();
    }

    /**
     * render print_r var if dev
     * @param $var
     */
    public function renderVar($var){
        $this->renderHTML('<pre>' . print_r($var, true) . '</pre>');
    }

    /**
     * render var_dump var if dev
     * @param $var
     */
    public function renderDump($var){
        ob_start();
        var_dump($var);
        $str = ob_get_contents();
        ob_end_clean();

        $this->renderHTML($str);
    }

    public function ok(){
        $this->response->setStatus(200);
        $this->send();
    }

    /**
     * @param string $message
     */
    public function todo($message = ''){
        $this->render('system/todo.html', array('message' => $message));
    }

    /**
     * @param string $message
     * @throws \framework\exceptions\NotFoundException
     */
    public function notFound($message = ''){
        throw new NotFoundException($message);
    }

    /**
     * @param $what
     * @param string $message
     */
    public function notFoundIfEmpty($what, $message = ''){
        if (empty($what))
            $this->notFound($message);
    }


    /**
     * @return Controller
     */
    public static function current(){
        return self::$current;
    }
}