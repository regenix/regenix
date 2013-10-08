<?php
namespace tests\mvc;

use regenix\mvc\http\Request;
use tests\RegenixTest;

class RequestTest extends RegenixTest {

    private $lastSERVER;

    public function __construct(){
        $this->requiredOk(URLTest::type);
    }

    protected function onGlobalBefore(){
        parent::onGlobalBefore();
        $this->lastSERVER = $_SERVER;

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/path/to?key=value';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'my super browser 1.2.0';
        $_SERVER['HTTP_REFERER'] = 'http://regenix.ru/';
        $_SERVER['SERVER_PORT'] = 80;

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru-RU, ru';
    }

    protected function onGlobalAfter(){
        $_SERVER = $this->lastSERVER;
    }

    public function testCreateGlobal(){
        $request = Request::createFromGlobal(array(
            'accept-language' => 'ru-RU, ru;q=0.8,en-US'
        ));

        $this->assert($request->isMethod('Get'));
        $this->assert($request->isMethods(array('get', 'post')));
        $this->assertEqual('GET', $request->getMethod());

        $this->assertEqual('example.com', $request->getHost());
        $this->assertEqual('my super browser 1.2.0', $request->getUserAgent());
        $this->assertEqual('http://regenix.ru/', $request->getReferer());
        $this->assertEqual('key=value', $request->getQuery());
        $this->assertEqual('/path/to', $request->getPath());

        $this->assertEqual(array('ru', 'en-US'), $request->getLanguages());
        $this->assert($request->isBase('http://example.com'));
    }
}