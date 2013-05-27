<?php
namespace tests;

use regenix\logger\Logger;
use regenix\logger\LoggerHandler;

Logger::debug('Init logger test');

class LoggingTest extends RegenixTest {

    public function __construct(){
        $this->requiredOk(ClassloaderTest::type);
    }

    public function onBefore(){
        Logger::clearHandlers();
    }

    public function simple(){
        Logger::registerHandler(Logger::LEVEL_DEBUG, new LoggerTestHandler());
        Logger::debug('Test');
        $this->assertStrongEqual('Test', LoggerTestHandler::$log[0]);
    }

    public function level(){
        Logger::registerHandler(Logger::LEVEL_INFO, new LoggerTestHandler());

        Logger::debug('Not write');
        $this->assertNotEqual('Not write', LoggerTestHandler::$log[0]);

        Logger::info('Info Write');
        $this->assertEqual('Info Write', LoggerTestHandler::$log[0]);

        Logger::warn('Warn Write');
        $this->assertEqual('Warn Write', LoggerTestHandler::$log[0]);
    }

    public function multi(){
        Logger::registerHandler(Logger::LEVEL_ERROR, new LoggerMultiTestHandler());
        Logger::registerHandler(Logger::LEVEL_WARN, new LoggerMultiTestHandler());

        Logger::info('log info');
        $this->assertArraySize(0, LoggerMultiTestHandler::$log);

        Logger::warn('log warn');
        $this->assertArraySize(1, LoggerMultiTestHandler::$log);
        $this->assertEqual('log warn', LoggerMultiTestHandler::$log[0][0]);

        Logger::error('log error');
        $this->assertEqual('log error', LoggerMultiTestHandler::$log[1][0]);
        $this->assertEqual('log error', LoggerMultiTestHandler::$log[2][0]);
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