<?php
namespace framework\widgets\inputs;

use framework\lang\String;
use framework\widgets\Widget;

abstract class FieldWidget extends Widget {

    const INPUT_TYPE = '';

    /** @var string */
    public $name;

    /** @var mixed */
    public $value;

    /** @var string */
    public $id;

    /** @var string */
    public $class;

    /** @var bool */
    public $enabled = true;

    public function __construct(array $args = array()){
        foreach($args as $code => $value){
            $this->{$code} = $value;
        }
    }

    /**
     * @param array $codes
     * @return array
     */
    protected function makeHtmlArgs(array $codes){
        $params = array();
        foreach($codes as $code){
            $value = null;

            if (method_exists($this, $method = 'getPrint' . $code)){
                $value = (string)$this->{$method}();
            }

            if ($value || $value = htmlspecialchars((string)$this->{$code}))
                $params[] = $code . '="' . $value . '"';
        }
        return $params;
    }

    protected function makeHtmlArgsWithClass(array $codes){
        $params = $this->makeHtmlArgs($codes);

        if (static::INPUT_TYPE)
            $params[] = ' type="' . static::INPUT_TYPE . '"';

        $class = 'cls-' . array_pop(explode('\\', get_class($this)));
        if ($value = $this->class)
            $class .= ' ' . htmlspecialchars($value);

        if (method_exists($this, 'getPrintClass'))
            $class = $this->getPrintClass();

        $params[] = ' class="' . $class . '"';

        if (!$this->enabled)
            $params[] = ' disabled';

        return implode(' ', $params);
    }

    protected function getHtmlArgs(){
        return $this->makeHtmlArgsWithClass(array('value', 'name', 'id'));
    }

    protected function getContent(){
        return String::format('<input %s/>', $this->getHtmlArgs());
    }
}