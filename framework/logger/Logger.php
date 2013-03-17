<?php

namespace framework\logger;

abstract class Logger {

    const type = __CLASS__;

    abstract function log($var);
    abstract function warn($var);
    abstract function err($var);
}
