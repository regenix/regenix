<?php
namespace regenix\mvc\binding;

interface BindValue {

    const i_type = __CLASS__;

    /**
     * @param $value string
     * @return null
     */
    public function onBindValue($value);
}