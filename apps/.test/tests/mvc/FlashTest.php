<?php
namespace tests\mvc;

use framework\mvc\Flash;
use tests\RegenixTest;

class FlashTest extends RegenixTest {

    /** @var Flash */
    protected $flash;

    public function __construct(){
        $this->requiredOk(SessionTest::type);
    }

    protected function onGlobalBefore(){
        parent::onGlobalBefore();

        $this->flash = Flash::current();
    }

    public function onBefore(){
        $this->flash->touchAll();
    }

    public function testTouch(){
        $this->assertType('object', $this->flash);

        $this->flash->put('flash_key', 'flash_value');
        $this->flash->touch('flash_key');
        $this->assertEqual('flash_value', $this->flash->get('flash_key'), 'Check flash touch value');

        $this->flash->touch('flash_key');
        $this->assertNotRequire($this->flash->get('flash_key'), 'Check flash 2x touch value');
    }

    public function testTouchAll(){
        $this->flash->put('key', 'value');
        $this->flash->touchAll();
        $this->assertEqual('value', $this->flash->get('key'), 'Check touch all value');

        $this->flash->touchAll();
        $this->assertNotRequire($this->flash->get('key'));
    }

    public function testKeep(){
        $this->flash->put('key', 'value');

        $this->flash->keep('key');
        $this->flash->touchAll();
        $this->flash->touchAll();

        $this->assertEqual('value', $this->flash->get('key'));

        $this->flash->touchAll();
        $this->assertNotRequire($this->flash->get('key'));
    }

    public function testMessages(){
        $this->flash->error('my_error');
        $this->flash->warning('my_warning');
        $this->flash->success('my_success');

        $this->flash->touchAll();

        $this->assertEqual('my_error', $this->flash->error(), 'Msg error');
        $this->assertEqual('my_warning', $this->flash->warning(), 'Msg warning');
        $this->assertEqual('my_success', $this->flash->success(), 'Msg success');
    }

    public function testUtils(){
        $this->flash->put('key', 'value');
        $this->assert($this->flash->has('key'), 'Has method');

        $this->flash->remove('key');
        $this->assertNot($this->flash->has('key'), 'Remove method');
    }
}