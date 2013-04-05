<?php
namespace framework\mvc;


class ActiveRecord extends AbstractModel {

    public function save(array $options = array()){
        static::getService()->save($this, $options);
    }

    public function delete(array $options = array()){
        static::getService()->remove($this, $options);
    }
}