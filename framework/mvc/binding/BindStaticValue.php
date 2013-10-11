<?php
namespace regenix\mvc\binding;

interface BindStaticValue {

    const bindStaticValue_type = __CLASS__;

    /**
     * @param $value string
     * @param null $name
     * @return object
     */
    public static function onBindStaticValue($value, $name = null);
}