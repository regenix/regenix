<?php
namespace framework\widgets\inputs;

class SelectFieldWidget extends FieldWidget {

    /** @var array */
    public $variants = array();

    /** @var bool */
    public $multiple = false;

    /** @var int */
    public $size = 1;

    public function getContent(){
        $result  = '<select ' . $this->makeHtmlArgsWithClass(array('name', 'id', 'class', 'size'));
        if ($this->multiple)
            $result .= ' multiple';

        $result .= ">\n";
        foreach($this->variants as $code => $value){
            $result .= '<option value="' . $code . '"';
            if ($code == $this->value)
                $result .= ' selected';

            $result .= '>' . $value . '</option>' . "\n";
        }

        return $result . '</select>';
    }
}