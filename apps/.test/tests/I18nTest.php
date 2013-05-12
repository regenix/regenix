<?php
namespace tests;


use framework\libs\I18n;

class I18nTest extends  BaseTest {

    const type = __CLASS__;

    public function __construct(){
        $this->requiredOk(LangTest::type);
    }

    public function onGlobalBefore(){
        I18n::setMessages('my', array(
            'key' => 'message',
            'key {0} x' => 'message {0} x',
            'key {0} x {1} z' => 'message {0} x {1} z'
        ));
        I18n::setLang('my', false);

        I18n::setMessages('xz', array(
            'field' => 'alert'
        ));
    }

    public function simple(){
        $this->eq('my', I18n::getLang());

        $this->eq('message', I18n::get('key'));
        $this->eq('message', I18n::get('message'));
    }

    public function format(){
        $this->eq('message 123 x', I18n::get('message {0} x', 123));
        $this->eq('message 123 x', I18n::get('message {0} x', array(123)));

        $this->eq('message 123 x abc z', I18n::get('message {0} x {1} z', 123, 'abc'));
        $this->eq('message 123 x abc z', I18n::get('message {0} x {1} z', array(123, 'abc')));
    }

    public function multi(){
        $this->isTrue(I18n::availLang('xz'));

        I18n::setLang('xz', false);
        $this->eq('alert', I18n::get('field'));
        $this->eq('key', I18n::get('key'));

        I18n::setLang('my', false);
        $this->eq('message', I18n::get('key'));
    }
}