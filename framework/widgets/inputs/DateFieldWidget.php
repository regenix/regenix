<?php
namespace framework\widgets\inputs;

class DateFieldWidget extends TextFieldWidget {

    /** @var string */
    public $format = 'Y-m-d';

    protected function getPrintValue(){
        $date = $this->value;
        if (!($date instanceof \DateTime)){
            $date = new \DateTime();

            if (is_numeric($this->value))
                $date->setTimestamp((int)$this->value);
            else
                $date->modify($this->value);
        }

        return $date->format($this->format);
    }

    protected function makeHtmlArgsWithClass(array $codes){
        return parent::makeHtmlArgsWithClass($codes) . ' timestamp="' . strtotime($this->value) . '"';
    }
}