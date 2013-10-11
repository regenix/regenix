<?php

namespace regenix\libs\captcha;

use regenix\lang\CoreException;
use regenix\lang\DI;
use regenix\lang\IClassInitialization;
use regenix\lang\Singleton;
use regenix\mvc\http\session\Session;
use regenix\mvc\providers\ResponseProvider;
use kcaptcha\KCaptcha;

/**
 * TODO: add options feature
 * Class Captcha
 * @package regenix\libs
 */
class Captcha implements IClassInitialization, Singleton {

    const type = __CLASS__;
    const SESSION_KEY = '__CAPTCHA_word';

    /** @var string */
    protected $keyString;

    /** @var \regenix\mvc\Session */
    protected $session;

    protected function __construct(Session $session){
        $this->session = $session;
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
        $session = DI::getInstance(Session::type);
        $session_word = $session->get(self::SESSION_KEY);
        if ($session_word === null)
            return false;

        return strtolower($session_word) === strtolower($word);
    }

    public static function checkAvailable(){
        if (!extension_loaded('gd'))
            throw new CoreException('Captcha feature needs installed and enabled `GD2` extension');

        if (!class_exists('\\kcaptcha\\KCaptcha'))
            throw new CoreException('KCaptcha vendor library not found, `vendor/kcaptcha/` not found');
    }

    public static function initialize(){
        self::checkAvailable();
        ResponseProvider::register(ResponseCaptchaProvider::type);
    }

    /**
     * @return Captcha
     */
    public static function getInstance() {
        return DI::getInstance(__CLASS__);
    }
}

