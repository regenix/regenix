<?php
use regenix\core\Regenix;

    define('REGENIX_DEBUG', true);

    // require main file
    require __DIR__ . '/framework/include.php';

    // add external application
    //Regenix::addExternalApp('E:/apps/JupiterBackend/tests');

    // If you generate regenix build file via `regenix framework-build`
    Regenix::requireBuild();

    // Init apps
    Regenix::initWeb(__DIR__);
