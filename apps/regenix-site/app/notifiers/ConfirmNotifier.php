<?php
namespace notifiers;

use framework\mvc\Mailer;

class ConfirmNotifier extends Mailer {

    public function welcome($to){
        $this
            ->setFrom('bot@dim-s.net', 'Regenix Framework')
            ->setSubject('Welcome to site, ' . $to)
            ->addRecipient($to);

        return $this->send();
    }
}