<?php
use regenix\Regenix;

    //define('IS_CORE_DEBUG', true);

    // require main file
    require 'framework/include.php';

    // add external application
    //Regenix::addExternalApp('E:/apps/JupiterBackend/tests');

    // Init apps
    Regenix::initWeb(__DIR__);