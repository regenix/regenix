<?php
namespace framework\widgets\inputs;

use framework\widgets\panels\PanelWidget;

class DateTimeFieldWidget extends PanelWidget {

    /** @var DateFieldWidget */
    protected $dateField;

    /** @var TimeFieldWidget */
    protected $timeField;

    /** @var string */
    public $name = '';

    /** @var string */
    public $class = '';

    /** @var string */
    public $id = '';

    /** @var mixed */
    public $value;

    public function __construct(array $args = array()){
        parent::__construct($args);

        $this->dateField = new DateFieldWidget();
        $this->timeField = new TimeFieldWidget();

        $this->add($this->dateField);
        $this->add($this->timeField);
    }

    protected function onRender(){
        $this->dateField->id = $this->id;

        $this->dateField->value = $this->value;
        $this->timeField->value = $this->value;

        if ($this->name){
            $this->dateField->name = $this->name . '[date]';
            $this->timeField->name = $this->name;
        }

        if ($this->class){
            $this->dateField->class = $this->class;
            $this->timeField->class = $this->class;
        }
    }
}