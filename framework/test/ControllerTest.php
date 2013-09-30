<?php
namespace regenix\test;

use regenix\Regenix;
use regenix\lang\CoreException;
use regenix\lang\DI;
use regenix\lang\String;
use regenix\mvc\Controller;
use regenix\mvc\Flash;
use regenix\mvc\Request;
use regenix\mvc\RequestBody;
use regenix\mvc\Response;
use regenix\mvc\Session;

abstract class ControllerTest extends UnitTest {

    /** @var Flash */
    protected $flash;

    /** @var Session */
    protected $session;

    /** @var Response */
    protected $response;

    public function __construct(){
    }

    /**
     * @param $httpMethod
     * @param $relativeUrl
     * @param $headers
     */
    protected function newRequest($httpMethod, $relativeUrl, $headers = array()){
        $app = Regenix::app();
        $request = new Request($headers);
        $request->setMethod($httpMethod);
        $request->setUri($relativeUrl);

        $this->response = Regenix::processRequest($app, $request);
    }
}

class TestableSession extends Session {
    public function __construct(){}
}

class TestableFlash extends Flash {
    public function __construct(TestableSession $session){
        $this->session = $session;
    }
}

class TestableRequestBody extends RequestBody {

}