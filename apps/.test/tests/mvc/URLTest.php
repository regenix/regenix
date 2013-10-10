<?php
namespace tests\mvc;

use regenix\mvc\http\URL;
use tests\RegenixTest;

class URLTest extends RegenixTest {

    const type = __CLASS__;

    const HOST = 'example.com';
    const PORT = 12345;
    const PROTOCOL = 'http';
    const PATH = '/path/to/url';
    const QUERY = 'key1=value1&key2=value2';

    const URL = 'http://example.com:12345/path/to/url?key1=value1&key2=value2';

    public function testBuild(){
        $url = URL::build(self::HOST, self::PATH, self::QUERY, self::PROTOCOL, self::PORT);
        $this->assertEqual(self::URL, $url->getUrl(), 'Check build method');

        $url = URL::buildFromUri(self::HOST, self::PATH . '?' . self::QUERY, self::PROTOCOL, self::PORT);
        $this->assertEqual(self::URL, $url->getUrl(), 'Check build from uri');
    }

    public function testBase(){
        $url = new URL(self::URL);

        $this->assertStrongEqual(self::PROTOCOL, $url->getProtocol());
        $this->assertStrongEqual(self::HOST, $url->getHost());
        $this->assertStrongEqual(self::PORT, $url->getPort());
        $this->assertStrongEqual(self::PATH, $url->getPath());
        $this->assertStrongEqual(self::QUERY, $url->getQuery());

        $url2 = new URL($url);
        $this->assertEqual(self::URL, $url2->getUrl());
        $this->assertEqual(self::QUERY, $url2->getQuery());
        $this->assert($url->constraints($url2));

        $url = new URL('example.com?key=value1');
        $this->assertEqual('http', $url->getProtocol());
        $this->assertEqual(80, $url->getPort());
        $this->assertEqual('example.com', $url->getPath());
        $this->assertNotRequire($url->getHost());
        $this->assertEqual('key=value1', $url->getQuery());
    }

    public function testParseQuery(){
        $query = URL::parseQuery('key1=value1');
        $this->assertEqual(array('key1' => 'value1'), $query);

        $query = URL::parseQuery('key1=value1&key2=value2');
        $this->assertEqual(array('key1' => 'value1', 'key2' => 'value2'), $query);

        $query = URL::parseQuery('key[]=value1&key[]=value2');
        $this->assertEqual(array('key' => array('value1', 'value2')), $query);

        $query = URL::parseQuery('key[code]=value1&key[2]=value2');
        $this->assertEqual(array('key' => array('code' => 'value1', 2 => 'value2')), $query);
    }

    public function testConstraints(){
        $url = new URL(self::URL);
        $this->assert($url->constraints(new URL('http://example.com:12345')));
        $this->assert($url->constraints(new URL('http://example.com:12345/')));
        $this->assert($url->constraints(new URL('http://example.com:12345/path/to')));

        $url = new URL('http://example.com:80/');
        $this->assert($url->constraints(new URL('http://example.com/')));
    }
}