<?php
namespace tests;


use framework\libs\I18n;

class I18nTest extends  RegenixTest {

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
        $this->assertEqual('my', I18n::getLang());

        $this->assertEqual('message', I18n::get('key'));
        $this->assertEqual('message', I18n::get('message'));
    }

    public function format(){
        $this->assertEqual('message 123 x', I18n::get('message {0} x', 123));
        $this->assertEqual('message 123 x', I18n::get('message {0} x', array(123)));

        $this->assertEqual('message 123 x abc z', I18n::get('message {0} x {1} z', 123, 'abc'));
        $this->assertEqual('message 123 x abc z', I18n::get('message {0} x {1} z', array(123, 'abc')));
    }

    public function formatNamed(){
        $this->assertEqual('message 123 x XYZ', I18n::get('message {id} x {code}', array('id' => 123, 'code' => 'XYZ')));
    }

    public function multi(){
        $this->assert(I18n::availLang('xz'));

        I18n::setLang('xz', false);
        $this->assertEqual('alert', I18n::get('field'));
        $this->assertEqual('key', I18n::get('key'));

        I18n::setLang('my', false);
        $this->assertEqual('message', I18n::get('key'));
    }
}