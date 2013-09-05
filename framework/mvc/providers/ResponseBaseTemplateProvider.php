<?php

namespace regenix\mvc\providers;

use regenix\mvc\Response;
use regenix\mvc\template\BaseTemplate;

class ResponseBaseTemplateProvider extends ResponseProvider {

    const type = __CLASS__;

    const CLASS_TYPE = BaseTemplate::type;
    
    /** @var \regenix\mvc\template\BaseTemplate */
    private $template;
    
    public function __construct(Response $response) {
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