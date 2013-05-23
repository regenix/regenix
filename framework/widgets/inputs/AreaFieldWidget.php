<?php
namespace framework\widgets\inputs;

use framework\lang\String;
use framework\widgets\Widget;

class AreaFieldWidget extends FieldWidget {

    public function getContent(){
        $add = $this->makeHtmlArgsWithClass(array('name', 'id'));
        return '<textarea ' . $add . '>' . htmlspecialchars($this->value) . '</textarea>';
    }
}