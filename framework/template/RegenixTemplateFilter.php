<?php
namespace regenix\template;

use regenix\lang\Singleton;

interface RegenixTemplateFilter extends Singleton {
    const regenixTemplateFilter_type = __CLASS__;

    public function getName();
    public function call($value, array $args, RegenixTemplate $ctx);
}