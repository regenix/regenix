<?php
namespace regenix\console;

    use regenix\Core;

{
    $root = dirname(dirname(__DIR__));
    require $root . '/framework/include.php';

    Core::initConsole($root);
}