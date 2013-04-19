<?php

namespace framework\libs;

use framework\lang\IClassInitialization;
use framework\mvc\MIMETypes;
use framework\mvc\Response;
use framework\mvc\Session;
use framework\mvc\providers\ResponseProvider;
use kcaptcha\KCaptcha;

/**
 * TODO: add options feature
 * Class Captcha
 * @package framework\libs
 */
class Captcha implements IClassInitialization {

    const type = __CLASS__;
    const SESSION_KEY = '__CAPTCHA_word';
    const URL = '/system/captcha.img';

    /** @var string */
    protected $keyString;

    /** @var \framework\mvc\Session */
    protected $session;

    protected function __construct(){
        $this->session = Session::current();
    }

    /**
     * @return resource image
     */
    public function getImage(){
        $kcaptcha = new KCaptcha();
        $this->keyString = $kcaptcha->getKeyString();
        $this->session->put(self::SESSION_KEY, $this->keyString);

        return $kcaptcha->render();
    }

    /**
     * @return string
     */
    protected function getWord(){
        return $this->keyString;
    }

    /**
     * @param string $word
     * @return bool
     */
    public static function isValid($word){
        $session_word = Session::current()->get(self::SESSION_KEY);
        if ($session_word === null)
            return false;

        return strtolower($session_word) === strtolower($word);
    }

    public static function initialize(){
        ResponseProvider::register(ResponseCaptchaProvider::type);
    }

    private static $instance;

    /**
     * @return Captcha
     */
    public static function current(){
        if (self::$instance)
            return self::$instance;

        return self::$instance = new Captcha();
    }
}

class ResponseCaptchaProvider extends ResponseProvider {

    const type = __CLASS__;
    const CLASS_TYPE = Captcha::type;

    public function __construct(Response $response) {
        parent::__construct($response);

        /** @var $file Captcha */
        $captcha = $response->getEntity();
        $response->setContentType(MIMETypes::getByExt('jpg'));

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