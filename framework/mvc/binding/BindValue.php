<?php
namespace regenix\mvc\binding;

interface BindValue {

    const bindValue_type = __CLASS__;

    /**
     * @param $value string
     * @param null $name
     * @return null
     */
    public function onBindValue($value, $name = null);
}