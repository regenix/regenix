<?php
namespace framework\console;

    use framework\Core;

{
    $root = dirname(dirname(__DIR__));
    require $root . '/framework/Core.php';

    Core::initConsole($root);

    $commander = Commander::current();
    $commander->run();
}