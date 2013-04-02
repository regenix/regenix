<?php
require 'framework/Core.php';
use framework\Core;
define('ROOT', realpath(dirname(__FILE__)) . '/');

Core::init();
try {
    Core::processRoute();
} catch (\framework\exceptions\CoreException $e){
    Core::catchCoreException($e);
} catch (\ErrorException $e){
    Core::catchErrorException($e);
} catch (\Exception $e){
    Core::catchException($e);
}