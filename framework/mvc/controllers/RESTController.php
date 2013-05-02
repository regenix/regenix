<?php
namespace framework\mvc\controllers;

use framework\exceptions\NotFoundException;
use framework\mvc\Controller;

class RESTController extends Controller{

    private $errors = array();

    /**
     * Render return value as JSON
     * @param $result
     */
    protected function onReturn($result){
        if ($this->hasErrors())
            $this->renderJson(array('status' => 'fail', 'data' => $this->errors));
        else
            $this->renderJson(array('status' => 'success', 'data' => $result));
    }

    /**
     * @param NotFoundException $e
     */
    protected function onNotFound(NotFoundException $e){
        $this->addError('not_found');
        $this->onReturn(null);
    }

    /**
     * @param string $message
     */
    protected function addError($message){
        $this->errors[] = $message;
    }

    /**
     * @return bool
     */
    protected function hasErrors(){
        return sizeof($this->errors) > 0;
    }

    /**
     * @param $params
     */
    protected function onBindParams(&$params){
        $params = $this->body->asJSON();
    }

    /**
     * @param \Exception $e
     */
    protected function onException(\Exception $e){
        $this->response->setStatus(500);
        $this->renderJSON(array('status' => 'error', 'data' => null, 'message' => $e->getMessage()));
    }
}