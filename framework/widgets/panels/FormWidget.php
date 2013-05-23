<?php
namespace framework\widgets\panels;

use framework\lang\String;
use framework\widgets\inputs\FieldWidget;

class FormWidget extends PanelWidget {

    /** @var string */
    public $action = '';

    /** @var string */
    public $method = 'POST';

    /** @var string */
    public $name = '';

    /** @var string */
    public $id = '';

    /** @var string */
    public $class = '';

    protected function getContent(){
        $result = String::format('<form action="%s" method="%s"', $this->action, $this->method);
            if ($this->name)
                $result .= ' name="' . htmlspecialchars($this->name) . '"';
            if ($value = $this->id)
                $result .= ' id="' . htmlspecialchars($this->id) . '"';
            if ($value = $this->class)
                $result .= ' class="' . htmlspecialchars($this->class) . '"';

        $result .= ">\n";
        $result .= parent::getContent();
        $result .= "\n</form>";

        return $result;
    }
}