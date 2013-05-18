<?php
define('IS_CORE_DEBUG', true);

require 'framework/Core.php';
use framework\Core;
define('ROOT', str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(__FILE__))) . '/');

try {
    Core::init();
    Core::processRoute();
} catch (\framework\exceptions\CoreException $e){
    Core::catchCoreException($e);
} catch (\ErrorException $e){
    Core::catchErrorException($e);
} catch (\Exception $e){
    Core::catchException($e);
}