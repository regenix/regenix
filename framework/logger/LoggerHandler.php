<?php
namespace regenix\logger;

interface LoggerHandler {
    public function writeLog($level, array $args);
}