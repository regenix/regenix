<?php
namespace regenix\template;

interface RegenixTemplateTag {

    const regenixTemplateTag_type = __CLASS__;

    public function getName();
    public function call($args, RegenixTemplate $ctx);
}
