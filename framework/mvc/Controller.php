<?php

namespace framework\mvc {

use framework\exceptions\CoreException;
use framework\exceptions\NotFoundException;
use framework\io\File;
use framework\mvc\Response;
use framework\mvc\template\TemplateLoader;
use framework\lang\String;
use framework\mvc\MIMETypes;

abstract class Controller {

    const type = __CLASS__;

    /** @var Response */
    public $response;

    /** @var Request */
    public $request;

    /** @var Session */
    public $session;

    /** @var Flash */
    public $flash;
    
    /** @var RequestQuery */
    public $query;

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

        $this->request  = Request::current();
        $this->session  = Session::current();
        $this->flash    = Flash::current();

        $this->response = new Response();
        $this->query    = new RequestQuery();
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
        if ( IS_DEV )
            $this->renderHTML('<pre>' . print_r($var, true) . '</pre>');
        else
            $this->send();
    }

    /**
     * render var_dump var if dev
     * @param $var
     */
    public function renderDump($var){
        if ( IS_DEV ){
            ob_start();
            dump($var);
            $str = ob_get_contents();
            ob_end_clean();

            $this->renderHTML($str);
        } else
            $this->send();
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
}

namespace controllers {

    use framework\mvc\Controller;

    function render($template = false, array $args = array()){
        Controller::current()->render($template, $args);
    }

    function renderTemplate($template, array $args = array()){
        Controller::current()->renderTemplate($template, $args);
    }

    function renderText($text){
        Controller::current()->renderText($text);
    }

    function renderHTML($html){
        Controller::current()->renderHTML($html);
    }

    function renderJSON($object){
        Controller::current()->renderJSON($object);
    }

    function notFound($message = ''){
        Controller::current()->notFound($message);
    }

    function notFoundIfEmpty($what, $message = ''){
        Controller::current()->notFoundIfEmpty($what, $message);
    }

    function todo($message = ''){
        Controller::current()->todo($message);
    }

    function redirect($url, $permanent = false){
        Controller::current()->redirect($url, $permanent);
    }

    function put($name, $arg){
        Controller::current()->put($name, $arg);
    }

    function putAll(array $args){
        Controller::current()->putAll($args);
    }
}