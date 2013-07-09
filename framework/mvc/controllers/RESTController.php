<?php
namespace regenix\mvc\controllers;

use regenix\exceptions\HttpException;
use regenix\mvc\Controller;

class RESTController extends Controller {

    private $errorMessages = array();

    /**
     * @param $errorMessage
     */
    protected function addUserError($errorMessage){
        $this->errorMessages[] = $errorMessage;
    }

    /**
     * Clear all validation and user errors.
     */
    protected function clearErrors(){
        $this->errorMessages = array();
        $this->validators = array();
    }

    /**
     * @return bool
     */
    protected function hasErrors(){
        return (!!$this->errorMessages) || parent::hasErrors();
    }

    /**
     * @param string $message
     */
    protected function fail($message = ''){
        $this->renderJson(array('status' => 'fail', 'message' => $message));
    }

    /**
     * Render return value as JSON
     * @param $result
     */
    protected function onReturn($result){
        if ($this->hasErrors()){
            $errors = $this->errorMessages;
            foreach($this->validators as $validator){
                $errors = array_merge($errors, $validator->getErrors());
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