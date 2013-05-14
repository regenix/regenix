<?php
namespace tests\config;

use framework\config\PropertiesConfiguration;
use framework\io\File;
use tests\BaseTest;
use tests\ClassloaderTest;

class PropertiesTest extends BaseTest {

    /** @var PropertiesConfiguration */
    protected $config;

    public function __construct(){
        $this->requiredOk(ClassloaderTest::type);
    }

    public function onGlobalBefore(){
        $this->config = new PropertiesConfiguration( new File(__DIR__ . '/test.properties') );
    }

    public function add(){
        $this->config->addProperty('my_key', 'my_value');
        $this->eq('my_value', $this->config->get('my_key'));
    }

    public function escape(){
        $this->eq('value=space', $this->config->get('key=space'));
    }

    public function env(){
        $this->config->setEnv('env1');
        $this->eq('value_env1', $this->config->get('key'));

        $this->config->setEnv('env2');
        $this->eq('value_env2', $this->config->get('key'));

        $this->config->setEnv(false);
        $this->eq('value', $this->config->get('key'));
    }

    public function clears(){
        $this->config->clearProperty('key');
        $this->isFalse($this->config->containsKey('key'));

        $this->config->clearProperty('env1.key');
        $this->isFalse($this->config->containsKey('env1.key'));
    }
}