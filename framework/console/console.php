<?php
namespace framework\console;

    use framework\Core;
    use framework\lang\ClassScanner;

{
    ini_set('display_errors', 'Off');
    set_time_limit(0);
    error_reporting(E_ALL ^ E_NOTICE);
    header_remove();

    define('IS_DEV', true);
    define('IS_PROD', false);
    define('APP_MODE', 'dev');

    require 'framework/lang/ClassScanner.php';
    ClassScanner::registerDefault(ROOT);

    defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
    define('CONSOLE_STDOUT', fopen('php://stdout', 'w+'));

    $commander = Commander::current();
    $commander->run();
}