<?php

namespace framework\logger;

abstract class Logger {

    abstract function log($var);
    abstract function warn($var);
    abstract function err($var);
}
