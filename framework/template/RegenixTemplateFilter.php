<?php
namespace regenix\template;

interface RegenixTemplateFilter {
    const i_type = __CLASS__;

    public function getName();
    public function call($value, array $args, RegenixTemplate $ctx);
}