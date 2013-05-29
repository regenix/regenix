<?php
namespace regenix\console;

    use regenix\Regenix;

{
    $root = dirname(dirname(__DIR__));
    require $root . '/framework/include.php';

    Regenix::initConsole($root);
}