<?php

namespace framework\mvc\providers;

use framework\mvc\template\BaseTemplate;

class ResponseBaseTemplateProvider extends ResponseProvider {

    const type = __CLASS__;

    const CLASS_TYPE = BaseTemplate::type;
    
    /** @var \framework\mvc\template\BaseTemplate */
    private $template;
    
    public function __construct(\framework\mvc\Response $response) {
        parent::__construct($response);
        $this->template = $response->getEntity();
        $response->setContentType( 'text/html' );
    }
    
    public function onBeforeRender(){
        $this->template->onBeforeRender();
    }


    public function getContent() {
        return $this->template->getContent();
    }

    public function render() {
        $this->template->render();
    }
}