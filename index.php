<?php
//define('IS_CORE_DEBUG', true);
use framework\Core;
require 'framework/Core.php';

try {
    Core::init(__DIR__);
    Core::processRoute();
} catch (Exception $e){
    Core::catchException($e);
}