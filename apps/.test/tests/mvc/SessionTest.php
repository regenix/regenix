<?php
namespace tests\mvc;

use framework\mvc\Session;
use tests\BaseTest;

class SessionTest extends BaseTest {

    const type = __CLASS__;

    /** @var Session */
    protected $session;

    public function onGlobalBefore(){
        $this->session = Session::current();
    }

    public function simple(){
        $this->req($this->session->getId());

        $this->session->put('my_key', 123);
        $this->eq(123, $this->session->get('my_key'));

        $this->session->clear();
        $this->isFalse($this->session->has('my_key'));

        $this->session->putAll(array(
            'key1' => 111,
            'key2' => 222
        ));
        $this->arraySize(2, $this->session->all());
        $this->eq(111, $this->session->get('key1'));
        $this->eq(222, $this->session->get('key2'));

        $this->session->remove('key1');
        $this->isFalse($this->session->has('key1'));
    }
}