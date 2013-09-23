<?php
namespace regenix\test;

use regenix\lang\CoreException;
use regenix\lang\String;
use regenix\mvc\Controller;

abstract class ControllerTest extends UnitTest {

    /** @var Controller */
    protected $controller;

    /** @var \ReflectionMethod */
    protected $actionMethod;

    public function __construct(Controller $controller){
        $this->controller = $controller;
    }

    /**
     * @param null $method
     * @throws \regenix\lang\CoreException
     * @return ControllerRequest
     */
    protected function newRequest($method = null){
        if (!$method){
            $name = $this->currentMethod->getName();
            if (String::startsWith($name, 'test'))
                $name = String::substring($name, 4);
        } else {
            $name = $method;
        }

        if (!method_exists($this->controller, $name)){
            $this->actionMethod = null;
        } else {
            $this->actionMethod = new \ReflectionMethod(
                $this->controller, $name
            );
            if ($this->actionMethod->getDeclaringClass()->isAbstract())
                $this->actionMethod = null;
        }

        if ($this->actionMethod == null)
            throw new CoreException('Method "%s" is not exist or not a due action for "%s" controller',
                $name, get_class($this->controller));

        return new ControllerRequest($this->controller, $this->actionMethod);
    }
}

final class ControllerRequest {

    /** @var Controller */
    private $controller;

    /** @var \ReflectionMethod */
    private $method;

    /** @var array */
    private $headers;

    public function __construct(Controller $controller, \ReflectionMethod $method){
        $this->controller = $controller;
        $this->method = $method;
    }

    /**
     * @param $contentType
     * @return $this
     */
    public function setContentType($contentType){
        $this->addHeader('Content-Type', $contentType);
        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function addHeader($name, $value){
        $this->headers[$name] = $value;
        return $this;
    }
}