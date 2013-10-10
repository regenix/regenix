<?php
namespace regenix\exceptions;

use regenix\lang\CoreException;
use regenix\lang\String;
use regenix\mvc\http\Response;
use regenix\mvc\template\TemplateLoader;

class HttpException extends CoreException {

    const type = __CLASS__;

    const E_BAD_REQUEST      = 400;
    const E_UNAUTHORIZED     = 401;
    const E_PAYMENT_REQUIRED = 402;
    const E_NOT_FOUND        = 404;
    const E_FORBIDDEN        = 403;
    const E_METHOD_NOT_ALLOWED = 405;
    const E_NOT_ACCEPTABLE   = 406;
    const E_CONFLICT         = 409;
    const E_GONE             = 410;
    const E_LENGTH_REQUIRED  = 411;
    const E_UNSUPPORTED_MEDIA_TYPE = 415;

    const E_INTERNAL_SERVER_ERROR = 500;
    const E_NOT_IMPLEMENTED       = 501;
    const E_BAD_GATEWAY           = 502;
    const E_SERVICE_UNAVAILABLE   = 503;
    const E_GATEWAY_TIMEOUT       = 504;

    private $status = 0;

    public function __construct($status, $message = ''){
        $this->status = $status;
        parent::__construct(String::formatArgs($message, array_slice(func_get_args(), 2)));
    }

    /** @return int */
    public function getStatus(){
        return $this->status;
    }

    /**
     * @return Response
     */
    public function getTemplateResponse(){
        $response = new Response();
        $response->setStatus($this->getStatus());

        $template = TemplateLoader::load('.errors/' . $this->getStatus() . '.html', false);
        if (!$template)
            $template = TemplateLoader::load('.errors/500.html');

        $template->put('e', $this);
        $response->setEntity($template);
        return $response;
    }
}