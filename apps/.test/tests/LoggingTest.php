<?php
namespace tests;

use framework\logger\Logger;
use framework\logger\LoggerHandler;

Logger::debug('Init logger test');

class LoggingTest extends BaseTest {

    public function __construct(){
        $this->requiredOk(ClassloaderTest::type);
    }

    public function onBefore(){
        Logger::clearHandlers();
    }

    public function simple(){
        Logger::registerHandler(Logger::LEVEL_DEBUG, new LoggerTestHandler());
        Logger::debug('Test');
        $this->eqStrong('Test', LoggerTestHandler::$log[0]);
    }

    public function level(){
        Logger::registerHandler(Logger::LEVEL_INFO, new LoggerTestHandler());

        Logger::debug('Not write');
        $this->notEq('Not write', LoggerTestHandler::$log[0]);

        Logger::info('Info Write');
        $this->eq('Info Write', LoggerTestHandler::$log[0]);

        Logger::warn('Warn Write');
        $this->eq('Warn Write', LoggerTestHandler::$log[0]);
    }

    public function multi(){
        Logger::registerHandler(Logger::LEVEL_ERROR, new LoggerMultiTestHandler());
        Logger::registerHandler(Logger::LEVEL_WARN, new LoggerMultiTestHandler());

        Logger::info('log info');
        $this->arraySize(0, LoggerMultiTestHandler::$log);

        Logger::warn('log warn');
        $this->arraySize(1, LoggerMultiTestHandler::$log);
        $this->eq('log warn', LoggerMultiTestHandler::$log[0][0]);

        Logger::error('log error');
        $this->eq('log error', LoggerMultiTestHandler::$log[1][0]);
        $this->eq('log error', LoggerMultiTestHandler::$log[2][0]);
    }
}

class LoggerTestHandler extends LoggerHandler {

    public static $log;
    public function writeLog($level, array $args){
        self::$log = $args;
    }
}

class LoggerMultiTestHandler extends LoggerHandler {

    public static $log = array();
    public function writeLog($level, array $args){
        self::$log[] = $args;
    }
}