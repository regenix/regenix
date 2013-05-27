<?php
namespace tests\mvc;

use regenix\mvc\Session;
use tests\RegenixTest;

class SessionTest extends RegenixTest {

    const type = __CLASS__;

    /** @var Session */
    protected $session;

    public function onGlobalBefore(){
        $this->session = Session::current();
    }

    public function simple(){
        $this->assertRequire($this->session->getId());

        $this->session->put('my_key', 123);
        $this->assertEqual(123, $this->session->get('my_key'));

        $this->session->clear();
        $this->assertNot($this->session->has('my_key'));

        $this->session->putAll(array(
            'key1' => 111,
            'key2' => 222
        ));
        $this->assertArraySize(2, $this->session->all());
        $this->assertEqual(111, $this->session->get('key1'));
        $this->assertEqual(222, $this->session->get('key2'));

        $this->session->remove('key1');
        $this->assertNot($this->session->has('key1'));
    }
}