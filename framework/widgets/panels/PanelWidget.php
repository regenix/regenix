<?php
namespace framework\widgets\panels;

use framework\widgets\Widget;

class PanelWidget extends Widget {

    /** @var Widget[] */
    protected $items = array();

    public function add(Widget $item){
        $this->items[] = $item;
        return $this;
    }

    public function getItems(){
        return $this->items;
    }

    protected function getContent(){
        $result = '';
        foreach($this->items as $item){
            $result .= $item->render() . "\n";
        }

        return $result;
    }
}