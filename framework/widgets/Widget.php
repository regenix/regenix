<?php
namespace framework\widgets;

use framework\exceptions\CoreException;
use framework\exceptions\StrictObject;
use framework\lang\IClassInitialization;
use framework\lang\String;
use framework\mvc\template\TemplateLoader;

abstract class Widget extends StrictObject
    implements IClassInitialization {

    const TEMPLATE = '';

    /** @var bool */
    public $visible = true;


    /** @var array */
    protected $args;

    /** @var mixed|string */
    protected $uid;

    /** @var \ReflectionProperty[string] */
    protected $meta;

    public function __construct(array $args = array()){
        $class = get_class($this);
        $this->meta = self::$metaInfo[$class];

        foreach($args as $code => $value){
            if ($prop = $this->meta[$code])
                $this->{$code} = $value;
            else
                parent::__get($code);
        }

        $uid   = str_replace('\\', '.', get_class($this));
        if (String::startsWith($uid, 'widgets.'))
            $uid = substr($uid, 8);
        elseif (String::startsWith($uid, 'framework.widgets.'))
            $uid = substr($uid, 18);

        $this->uid = $uid;
    }

    public function __get($name){
        if ($this->meta[$name]){
            if (method_exists($this, $method = 'get' . $name)){
                return $this->{$method}($this->args[$name]);
            }
            return $this->args[$name];
        } else
            return parent::__get($name);
    }

    public function __set($name, $value){
        if ($this->meta[$name]){
            if (method_exists($this, $method = 'set' . $name)){
                $this->{$method}($value);
            }
            $this->args[$name] = $value;
        } else
            return parent::__set($name, $value);
    }

    protected function getTemplate(){
        if (static::TEMPLATE)
            return str_replace('\\', '/', static::TEMPLATE);
        else
            return str_replace('.', '/', $this->uid) . '.html';
    }

    protected function getContent(){
        $args['this'] = $this;

        $template = TemplateLoader::load('.widgets/' . $this->getTemplate());
        $template->putArgs($args);
        return (string)$template;
    }

    protected function onRender(){}

    /**
     * @return string
     */
    public function render(){
        $this->onRender();

        $result  = '';
        if ($this->visible)
            $result .= $this->getContent($this->args);

        return $result;
    }

    /**
     * @param string $uid
     * @param array $args
     * @throws static
     * @return Widget
     */
    public static function createByUID($uid, array $args = array()){
        $class = str_replace('.', '\\', $uid);
        if (class_exists($class = 'widgets\\' . $class))
            return new $class($args);
        else if (class_exists($extClass = $class . 'Widget'))
            return new $extClass($args);
        else if (class_exists($class = 'framework\\' . $class))
            return new $class($args);
        else if (class_exists($extClass = $class . 'Widget'))
            return new $extClass($args);
        else
            throw WidgetNotFoundException::formated('Widget "%s" not found', $uid);
    }

    /** @var array */
    private static $metaInfo = array();

    public static function initialize(){
        $class      = get_called_class();
        $reflection = new \ReflectionClass($class);

        if (!$reflection->isAbstract())
        foreach($reflection->getProperties() as $property){
            if (($property->isPublic() || $property->isProtected()) && !$property->isStatic()){
                $property->setAccessible(true);
                   self::$metaInfo[$class][$property->getName()] = $property;
            }
        }
    }
}

class WidgetNotFoundException extends CoreException {}