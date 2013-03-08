<?php

namespace framework\mvc\providers;

class ResponseBaseTemplateProvider extends ResponseProvider {
    
    const CLASS_TYPE = '\framework\mvc\template\BaseTemplate'; 
    
    /** @var \framework\mvc\template\BaseTemplate */
    private $template;
    
    public function __construct(\framework\mvc\Response $response) {
        parent::__construct($response);
        $this->template = $response->getEntity();
        $response->setContentType( 'text/html' );
    }
    
    public function getContent() {
        return $this->template->getContent();
    }

    public function render() { 
        $this->template->render();
    }
}