<?php
namespace regenix\libs\captcha;

use regenix\mvc\http\MimeTypes;
use regenix\mvc\http\Response;
use regenix\mvc\providers\ResponseProvider;

class ResponseCaptchaProvider extends ResponseProvider {

    const type = __CLASS__;
    const CLASS_TYPE = Captcha::type;

    public function __construct(Response $response) {
        parent::__construct($response);

        /** @var $file Captcha */
        $captcha = $response->getEntity();
        $response->setContentType(MimeTypes::getByExt('jpg'));

        $response->applyHeaders(array(
            'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache'
        ));
    }

    public function onBeforeRender(){}

    public function render(){
        /** @var $captcha Captcha */
        $captcha = $this->response->getEntity();

        $img = $captcha->getImage();
        imagejpeg($img);
    }
}
