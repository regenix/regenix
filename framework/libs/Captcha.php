<?php

namespace regenix\libs;

use regenix\lang\CoreException;
use regenix\lang\IClassInitialization;
use regenix\mvc\MIMETypes;
use regenix\mvc\Request;
use regenix\mvc\RequestBody;
use regenix\mvc\RequestQuery;
use regenix\mvc\Response;
use regenix\mvc\Session;
use regenix\mvc\providers\ResponseProvider;
use kcaptcha\KCaptcha;
    use regenix\validation\EntityValidator;
use regenix\validation\ValidationCallbackResult;
use regenix\validation\ValidationRequiresResult;
use regenix\validation\ValidationResult;
use regenix\validation\Validator;

/**
 * TODO: add options feature
 * Class Captcha
 * @package regenix\libs
 */
class Captcha implements IClassInitialization {

    const type = __CLASS__;
    const SESSION_KEY = '__CAPTCHA_word';
    const URL = '/system/captcha.img';

    /** @var string */
    protected $keyString;

    /** @var \regenix\mvc\Session */
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
        if (!extension_loaded('gd'))
            throw new CoreException('Captcha feature needs installed and enabled `GD2` extension');

        if (!class_exists('\\kcaptcha\\KCaptcha'))
            throw new CoreException('KCaptcha vendor library not found, `vendor/kcaptcha/` not found');

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

class CaptchaValidator extends Validator {

    private $checkWord;

    public function __construct($checkWord = null){
        if ($checkWord === null){
            $request = Request::current();
            if ($request->isMethod('GET'))
                $word = RequestQuery::current()->get('captcha_word');
            else {
                $body = new RequestBody();
                $word = $body->asQuery()->get('captcha_word');
            }
            $this->checkWord = $word;
        } else {
            $this->checkWord = $checkWord;
        }
    }

    protected function main(){
        $this->validateValue(
            $this->checkWord,
            'validation.result.captcha',
            new ValidationCallbackResult(function($value){
                $captcha = Captcha::current();
                return $captcha->isValid($value);
            })
        );
    }
}