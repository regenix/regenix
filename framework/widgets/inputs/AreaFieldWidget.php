<?php
namespace regenix\widgets\inputs;

use regenix\lang\String;
use regenix\widgets\Widget;

class AreaFieldWidget extends FieldWidget {

    public function getContent(){
        $add = $this->makeHtmlArgsWithClass(array('name', 'id'));
        return '<textarea ' . $add . '>' . htmlspecialchars($this->value) . '</textarea>';
    }
}