<?php
namespace regenix\mvc;

use regenix\lang\DI;
use regenix\libs\captcha\Captcha;
use regenix\i18n\I18n;
use regenix\libs\ImageUtils;
use regenix\mvc\http\MimeTypes;

class SystemController extends Controller {

    /**
     * Captcha
     */
    public function captcha(){
        $this->setUseSession(false);
        $captcha = DI::getInstance(Captcha::type);
        $this->render($captcha);
    }

    /**
     * I18N
     * @param $_lang
     */
    public function i18n_js($_lang){
        $this->setUseSession(false);

        if (!$_lang)
            $_lang = 'default';

        if (!I18n::availLang($_lang))
            $this->notFound();

        $messages = (array)I18n::getMessages($_lang);
        $out = "I18n_messages = " . json_encode($messages);

        $etag = I18n::getLangStamp($_lang);
        $this->response->cacheForETag($etag);

        if (!$this->request->isCachedEtag($etag)){
            $this->response->setContentType(MimeTypes::getByExt('js'));
            $this->renderText($out);
        } else {
            $this->response->setStatus(304);
            $this->send();
        }
    }
}