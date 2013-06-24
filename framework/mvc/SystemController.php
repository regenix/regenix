<?php
namespace regenix\mvc;

use regenix\libs\Captcha;
use regenix\libs\I18n;
use regenix\libs\ImageUtils;

class SystemController extends Controller {

    /**
     * Captcha
     */
    public function captcha(){
        $this->setUseSession(false);
        $this->render(Captcha::current());
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
            $this->response->setContentType(MIMETypes::getByExt('js'));
            $this->renderText($out);
        } else {
            $this->response->setStatus(304);
            $this->send();
        }
    }
}