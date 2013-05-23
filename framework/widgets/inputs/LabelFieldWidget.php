<?php
namespace framework\widgets\inputs;

use framework\lang\String;
use framework\libs\I18n;

class LabelFieldWidget extends FieldWidget {

    /** @var bool */
    public $require = false;

    protected function getPrintClass(){
        return $this->class . ($this->require ? ' require' : '');
    }

    public function getContent(){
        return String::format('<label %s>%s</label>',
            $this->makeHtmlArgsWithClass(array('name', 'id', 'class')),
            htmlspecialchars(I18n::get($this->value))
        );
    }
}