<?php
namespace tests\config;

use framework\io\File;
use framework\mvc\route\RouterConfiguration;
use tests\BaseTest;

class RoutesTest extends BaseTest {

    /** @var RouterConfiguration */
    protected $config;

    /** @var array */
    protected $routes;

    public function onGlobalBefore(){
        $this->config = new RouterConfiguration(new File(__DIR__ . '/route'));
        $this->routes = $this->config->getRouters();
    }

    public function main(){
        $this->isType('array', $this->routes);
        $this->eq('*', $this->routes[0]['method']);
        $this->eq('/index', $this->routes[0]['path']);
        $this->eq('.controllers.Application.index', $this->routes[0]['action']);

        $this->arraySize(3, $this->routes);
    }

    public function regex(){
        $this->eq('/{controller}/{method<[A-Ba-c0-9]+>}/', $this->routes[1]['path']);
        $this->eq('.controllers.{controller}.action{method}', $this->routes[1]['action']);
    }

    public function absolute(){
        $this->eq('.Abs.method', $this->routes[2]['action']);
    }
}