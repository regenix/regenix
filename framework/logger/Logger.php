<?php

namespace regenix\logger;

use regenix\core\Regenix;
use regenix\core\Application;
use regenix\lang\CoreException;
use regenix\lang\IClassInitialization;
use regenix\lang\String;

abstract class Logger implements IClassInitialization {

    const type = __CLASS__;

        const LEVEL_FATAL = 100;
        const LEVEL_ERROR = 99;
        const LEVEL_WARN  = 98;
        const LEVEL_INFO  = 97;
        const LEVEL_DEBUG = 96;

    protected static function writeLog($level, $args){
        foreach(self::$handlers as $info){
            if ( $info['level'] <= $level )
                $info['handler']->writeLog($level, $args);
        }
    }

    /**
     * @param int $level
     * @return string
     */
    public static function getLevelString($level){
        switch($level){
            case self::LEVEL_FATAL: return "fatal";
            case self::LEVEL_ERROR: return "error";
            case self::LEVEL_WARN: return "warn";
            case self::LEVEL_INFO: return "info";
            case self::LEVEL_DEBUG: return "debug";
        }
    }

    public static function getLevelOrd($level){
        switch(strtolower(trim($level))){
            case 'fatal': return self::LEVEL_FATAL;
            case 'error': return self::LEVEL_ERROR;
            case 'warn':  return self::LEVEL_INFO;
            case 'info': return self::LEVEL_INFO;
            case 'debug': return self::LEVEL_DEBUG;
            default:
                throw new CoreException('Logger level `%s` unknown', $level);
        }
    }

    public static function fatal($message){
        self::writeLog(self::LEVEL_FATAL, func_get_args());
    }

    public static function error($message){
        self::writeLog(self::LEVEL_ERROR, func_get_args());
    }

    public static function warn($message){
        self::writeLog(self::LEVEL_WARN, func_get_args());
    }

    public static function info($message){
        self::writeLog(self::LEVEL_INFO, func_get_args());
    }

    public static function debug($message){
        self::writeLog(self::LEVEL_DEBUG, func_get_args());
    }

    /**
     * @var LoggerHandler[]
     */
    private static $handlers = array();

    /**
     * @param int $level
     * @param LoggerHandler $handler
     */
    public static function registerHandler($level, LoggerHandler $handler){
        self::$handlers[] = array('level' => $level, 'handler' => $handler);
    }

    /**
     * clear all handlers
     */
    public static function clearHandlers(){
        self::$handlers = array();
    }

    /**
     * @param null|Application|array $configOrApp
     * @throws \regenix\lang\CoreException
     */
    public static function initialize($configOrApp = null){
        if ($configOrApp && $configOrApp instanceof Application)
            $app = $configOrApp;
        else
            $app = Regenix::app();

        if ($app){
            $enable   = $app->config->getBoolean('logger.enabled', true);
            $division = $app->config->getBoolean('logger.division', true);
            $level    = $app->config->getString('logger.level', 'info');
            $logPath  = $app->getLogPath();
        } else
            $enable = false;

        if ($configOrApp && is_array($configOrApp)){
            $enable = true;
            if (isset($configOrApp['division']))
                $division = $configOrApp['division'];
            else if (!isset($division))
                $division = true;

            if (isset($configOrApp['level']))
                $level = $configOrApp['level'];
            else if (!isset($level))
                $level = 'info';

            if (isset($configOrApp['logpath']))
                $logPath = $configOrApp['logpath'];
            else if (!isset($logPath))
                throw new CoreException('Please, specify log path for Logger');
        }

        if ( $enable ){
            self::registerHandler(self::getLevelOrd($level),
                new LoggerDefaultHandler($logPath, $division));
        }
    }
}