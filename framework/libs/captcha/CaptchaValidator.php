<?php
namespace regenix\libs\captcha;

use regenix\lang\DI;
use regenix\mvc\http\Request;
use regenix\mvc\http\RequestBody;
use regenix\mvc\http\RequestQuery;
use regenix\validation\Validator;
use regenix\validation\results\ValidationCallbackResult;

class CaptchaValidator extends Validator {

    private $checkWord;

    public function __construct($checkWord = null){
        if ($checkWord === null){
            $request = DI::getInstance(Request::type);
            if ($request->isMethod('GET')){
                $query = DI::getInstance(RequestQuery::type);
                $word = $query->get('captcha_word');
            } else {
                $body =  DI::getInstance(RequestBody::type);
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
                $captcha = DI::getInstance(Captcha::type);
                return $captcha->isValid($value);
            })
        );
    }
}