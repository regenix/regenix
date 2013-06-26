<?php
namespace regenix\mvc\controllers;

use regenix\exceptions\HttpException;
use regenix\mvc\Controller;

class RESTController extends Controller{

    private $errors = array();

    /**
     * Render return value as JSON
     * @param $result
     */
    protected function onReturn($result){
        if ($this->hasErrors()){
            $errors = array();
            foreach($this->validators as $validator){
                array_push($errors, $validator->getErrors());
            }
            $this->renderJson(array('status' => 'fail', 'data' => $errors));
        } else
            $this->renderJson(array('status' => 'success', 'data' => $result));
    }


    /**
     * @param HttpException $e
     */
    protected function onHttpException(HttpException $e){
        if ($e->getStatus() === HttpException::E_NOT_FOUND)
            $this->renderJson(array('status' => 'fail', 'code' => 'not_found', 'message' => $e->getMessage(), 'data' => null));
        else
            $this->renderJson(array('status' => 'fail', 'message' => $e->getMessage()));
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

class RESTException extends HttpException {

    public function __construct($message = ''){
        parent::__construct(HttpException::E_BAD_REQUEST, $message);
    }
}