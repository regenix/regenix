<?php
namespace framework\console;

    use framework\Core;
    use framework\lang\ClassLoader;
    use framework\lang\FrameworkClassLoader;

{
    ini_set('display_errors', 'Off');
    set_time_limit(0);
    error_reporting(E_ALL ^ E_NOTICE);
    header_remove();

    define('IS_DEV', true);
    define('IS_PROD', false);
    define('APP_MODE', 'dev');

    require 'framework/lang/ClassLoader.php';
    require 'framework/Core.php';

    Core::registerSystemClassLoader();

    defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
    define('CONSOLE_STDOUT', fopen('php://stdout', 'w+'));

    $commander = Commander::current();
    $commander->run();
}