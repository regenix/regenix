<?php

require 'framework/Core.php';

use framework\Core;

define('ROOT', realpath(dirname(__FILE__) . '/../') . '/');

Core::init();
Core::processRoute();