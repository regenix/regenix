<?php
namespace controllers\api;

use framework\mvc\Controller;
use ide\Project;
use ide\projects\MvcType;

abstract class Api extends Controller {

    /** @var array */
    protected $data = array();

    /** @var Project */
    protected $project = null;

    protected function onException(\Exception $e){
        $this->response->setStatus($e->getCode() ? $e->getCode() : 500);
        $this->renderJSON(array("error" => $e->getMessage()));
    }

    protected function onBefore(){
        if ($this->request->isMethod('POST')){
            $this->data    = $this->body->asJSON();
            if ($this->data['project'])
                $this->project = new Project($this->data['project'], new MvcType());
        }
    }
}