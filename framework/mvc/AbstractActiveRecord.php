<?php
namespace framework\mvc;


abstract class AbstractActiveRecord extends AbstractModel {

    public function save(array $options = array()){
        static::getService()->save($this, $options);
    }

    public function delete(array $options = array()){
        static::getService()->remove($this, $options);
    }
}