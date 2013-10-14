<?php
namespace regenix\template;

use regenix\lang\Singleton;

interface RegenixTemplateTag extends Singleton {

    const regenixTemplateTag_type = __CLASS__;

    public function getName();
    public function call($args, RegenixTemplate $ctx);
}
