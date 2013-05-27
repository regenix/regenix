<?php
namespace tests\config;

use regenix\lang\File;
use regenix\mvc\route\RouterConfiguration;
use tests\RegenixTest;

class RoutesTest extends RegenixTest {

    /** @var RouterConfiguration */
    protected $config;

    /** @var array */
    protected $routes;

    public function onGlobalBefore(){
        $this->config = new RouterConfiguration(new File(__DIR__ . '/route'));
        $this->routes = $this->config->getRouters();
    }

    public function main(){
        $this->assertType('array', $this->routes);
        $this->assertEqual('*', $this->routes[0]['method']);
        $this->assertEqual('/index', $this->routes[0]['path']);
        $this->assertEqual('.controllers.Application.index', $this->routes[0]['action']);

        $this->assertArraySize(3, $this->routes);
    }

    public function regex(){
        $this->assertEqual('/{controller}/{method<[A-Ba-c0-9]+>}/', $this->routes[1]['path']);
        $this->assertEqual('.controllers.{controller}.action{method}', $this->routes[1]['action']);
    }

    public function absolute(){
        $this->assertEqual('.Abs.method', $this->routes[2]['action']);
    }
}