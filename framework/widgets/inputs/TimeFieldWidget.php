<?php
namespace framework\widgets\inputs;

use framework\widgets\panels\PanelWidget;

class TimeFieldWidget extends PanelWidget {

    /** @var SelectFieldWidget */
    protected $hourSelect;

    /** @var SelectFieldWidget */
    protected $minuteSelect;

    /** @var string */
    public $name;

    /** @var string */
    public $class;

    /** @var string */
    public $value;

    public function __construct(array $args = array()){
        parent::__construct($args);

        $this->hourSelect = new SelectFieldWidget();
        $this->hourSelect->variants   = range(0, 23);

        $this->minuteSelect = new SelectFieldWidget();
        $this->minuteSelect->variants = range(0, 59);

        $this->add($this->hourSelect);
        $this->add(new LabelFieldWidget(array('value' => ':')));
        $this->add($this->minuteSelect);
    }

    protected function onRender(){
        $time = is_numeric($this->value) ? (int)$this->value : strtotime($this->value);
        $this->hourSelect->value = date('H', $time);
        $this->minuteSelect->value = date('i', $time);

        if ($this->name){
            $this->hourSelect->name   = $this->name . '[hour]';
            $this->minuteSelect->name = $this->name . '[min]';
        }

        $this->hourSelect->class   = $this->class;
        $this->minuteSelect->class = $this->class;
    }
}