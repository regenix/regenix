<?php
namespace framework\mvc;

use framework\libs\Captcha;
use framework\libs\I18n;

class SystemController extends Controller {

    /**
     * Captcha
     */
    public function captcha(){
        $this->render(Captcha::current());
    }

    /**
     * I18N
     * @param $_lang
     */
    public function i18n_js($_lang){
        if (!$_lang)
            $_lang = 'default';

        if (!I18n::availLang($_lang))
            $this->notFound();

        $messages = (array)I18n::getMessages($_lang);
        $out = "I18n_messages = " . json_encode($messages);
        $this->response->setContentType(MIMETypes::getByExt('js'));
        $this->renderText($out);
    }
}