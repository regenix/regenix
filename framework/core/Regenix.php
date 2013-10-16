<?php
namespace regenix\core;

use regenix\core\AbstractGlobalBootstrap;
use regenix\core\Application;
use regenix\core\SDK;
use regenix\exceptions\WrappedException;
use regenix\lang\SystemCache;
use regenix\lang\DI;
use regenix\lang\CoreException;
use regenix\exceptions\CoreStrictException;
use regenix\exceptions\HttpException;
use regenix\lang\File;
use regenix\lang\ClassScanner;
use regenix\lang\String;
use regenix\lang\SystemFileCache;
use regenix\libs\captcha\Captcha;
use regenix\logger\Logger;
use regenix\modules\Module;
use regenix\mvc\Controller;
use regenix\mvc\Result;
use regenix\mvc\http\Request;
use regenix\mvc\http\Response;
use regenix\mvc\http\URL;
use regenix\mvc\route\Router;
use regenix\mvc\route\RouterConfiguration;
use regenix\mvc\template\BaseTemplate;
use regenix\mvc\template\TemplateLoader;

final class Regenix {

    const type = __CLASS__;

    private static $requireBuild = false;

    /** @var string */
    private static $traceIncludes;

    /** @var float */
    private static $traceTime;

    /** @var int */
    private static $traceMemory;

    /** @var float */
    private static $startTime;

    /** @var int */
    private static $startMemory;

    /** @var string */
    private static $tempPath;

    /** @var string */
    private static $rootTempPath;

    /** @var Application[] */
    private static $apps = array();

    /** @var array */
    private static $profileLog = array();

    /** @var array */
    private static $externalApps = array();

    /** @var AbstractGlobalBootstrap */
    private static $bootstrap = null;

    // Util class
    private function __construct(){}

    /**
     * Get current version of regenix framework
     * @return string
     */
    public static function getVersion(){
        return '0.7';
    }

    /**
     * Get ip of current server
     * @return mixed
     */
    public static function getServerAddr(){
        return $_SERVER['SERVER_ADDR'];
    }

    /**
     * Get information about execute time, memory usage, etc.
     * @param bool $traceLog
     * @return array
     */
    public static function getDebugInfo($traceLog = false){
        $result = array(
            'time' => (microtime(true) - self::$startTime) * 1000,
            'memory' => memory_get_usage() - self::$startMemory,
            'memory_max' => memory_get_peak_usage()
        );
        if ($traceLog)
            $result['trace'] = self::$profileLog;

        return $result;
    }

    /**
     * @return mixed
     */
    public static function getLastTrace(){
        return end(self::$profileLog);
    }

    /**
     * Require Build script of Regenix
     */
    public static function requireBuild(){
        self::$requireBuild = true;
    }

    public static function init($rootDir, $inWeb = true){
        ini_set('display_errors', 'Off');
        error_reporting(0);

        self::$startTime   = self::$traceTime = microtime(true);
        self::$startMemory = self::$traceMemory = memory_get_usage();

        self::trace('Start init core');

        $rootDir = str_replace(DIRECTORY_SEPARATOR, '/', realpath($rootDir));
        if (substr($rootDir, -1) !== '/')
            $rootDir .= '/';

        define('ROOT', $rootDir);
        defined('REGENIX_STAT_OFF') or define('REGENIX_STAT_OFF', false);
        defined('REGENIX_DEBUG') or define('REGENIX_DEBUG', false);

        self::$rootTempPath = sys_get_temp_dir() . '/regenix_v' . self::getVersion() . '/';

        require REGENIX_ROOT . 'lang/PHP.php';
        CoreException::showOnlyPublic(!REGENIX_DEBUG);

        self::trace('PHP lang file included.');

        // register class loader
        ClassScanner::init($rootDir, array(REGENIX_ROOT));
        if (self::$requireBuild){
            $file = SYSTEM_CACHE_TMP_DIR . '/RegenixBuild.php';
            include $file;
        }

        self::trace('Add class path of framework done.');

        if ($inWeb){
            if (REGENIX_DEBUG)
                SystemFileCache::getTempDirectory();

            if (file_exists($globalFile = Application::getApplicationsPath() . '/GlobalBootstrap.php')){
                require $globalFile;
                $nameClass = '\\GlobalBootstrap';
                self::$bootstrap = new $nameClass();
                if (!(self::$bootstrap instanceof AbstractGlobalBootstrap))
                    throw new CoreException("Your GlobalBootstrap class should be inherited by AbstractGlobalBootstrap");
            }

            self::_registerTriggers();

            $done = false;
            if (REGENIX_STAT_OFF){
                $done = self::_registerCurrentAppFromCache();
            }

            if (!$done){
                self::_registerApps();
                self::trace('Register apps finish.');

                self::_registerCurrentApp();
                self::trace('Register current finish.');
            }

            if (!Regenix::app())
                register_shutdown_function(array(Regenix::type, '__shutdown'), null);

            if (defined('APP_MODE_STRICT') && APP_MODE_STRICT)
                set_error_handler(array(__CLASS__, '__errorHandler'));
        } else {
            error_reporting(0);
            set_time_limit(0);
            header_remove();

            define('IS_DEV', true);
            define('REGENIX_IS_DEV', true);
            define('IS_PROD', false);
            define('APP_MODE', 'dev');
        }

        if (!REGENIX_IS_DEV)
            error_reporting(0);
    }

    /**
     * Init for web src
     * @param $rootDir
     * @return Application
     */
    public static function initWeb($rootDir){
        try {
            self::init($rootDir);

            $app = Regenix::app();
            $request = DI::getInstance(Request::type);
            $request->setBasePath( $app->getUriPath() );

            self::processRequest($app, $request);
            return $app;
        } catch (\Exception $e){
            if (self::$bootstrap)
                self::$bootstrap->onException($e);

            self::catchException($e);
        }
    }

    /**
     * Add application from external directory
     * @param $path
     * @throws CoreException
     */
    public static function addExternalApp($path){
        if (self::$startTime){
            throw new CoreException('Method addExternalApp() should be called before initialization of the framework');
        }
        $path = str_replace('\\', '/', $path);
        if (substr($path, -1) !== '/')
            $path .= '/';

        if (!is_dir($path))
            die('Fatal error: The external application cannot be added from non-exists directory - "' . $path . '"');

        self::$externalApps[$path] = $path;
    }

    /**
     * Get current application
     * @return Application
     */
    public static function app(){
        return Application::current();
    }

    /**
     * Write trace log
     * @param $message
     */
    public static function trace($message){
        if (REGENIX_DEBUG === true || IS_DEV === true){
            $newIncludes = array_slice(get_included_files(), sizeof(self::$traceIncludes));

            self::$profileLog[] =
                array(
                    'message' => $message,
                    'debug' => array(
                        'time' => (microtime(true) - self::$traceTime) * 1000,
                        'memory' => (memory_get_usage() - self::$traceMemory)
                    ),
                    'includes' => $newIncludes
                );

            self::$traceIncludes = get_included_files();
            self::$traceTime   = microtime(true);
            self::$traceMemory = memory_get_usage();

        } else if (!self::$traceTime){
            self::$traceTime   = microtime(true);
            self::$traceMemory = memory_get_usage();
        }
    }

    private static function _registerTriggers(){
        SDK::registerTrigger('beforeRequest');
        SDK::registerTrigger('afterRequest');
        SDK::registerTrigger('finallyRequest');
        SDK::registerTrigger('registerTemplateEngine');
    }

    private static function _deployZip($zipFile){
        $name   = basename($zipFile, '.zip');
        $appDir = Application::getApplicationsPath() . $name . '/';

        // check directory exists
        if (file_exists($appDir)){
            $dir = new File($appDir);
            $dir->delete();
            $dir->mkdirs();
        }

        $zip = new \ZipArchive();
        if (self::$bootstrap)
            self::$bootstrap->onBeforeDeploy($zip, $appDir);

        if ($zip->open($zipFile)){
            $result = $zip->extractTo($appDir);
            if (!$result)
                throw new CoreException('The zip archive "%s" cannot be extracted to apps directory', basename($zipFile));

            $zip->close();
        }

        if (self::$bootstrap)
            self::$bootstrap->onAfterDeploy($zip, $appDir);

        $file = new File($zipFile);
        $file->delete();
    }

    private static function _deploy(){
        foreach (glob(Application::getApplicationsPath() . "*.zip") as $zipFile) {
            self::_deployZip($zipFile);
        }
    }

    private static function _registerApps(){
        $file = new File(Application::getApplicationsPath());

        if (self::$bootstrap)
            self::$bootstrap->onBeforeRegisterApps($file);

        $paths = array();
        foreach(self::$externalApps as $path){
            $paths[] = new File($path);
        }

        $key = '$rgx.' . sha1($file->getPath());
        if (REGENIX_STAT_OFF){
            $files = SystemCache::get($key);
        } else {
            $files = SystemCache::getWithCheckFile($key, $file->getPath());
        }

        if (!is_array($files)){
            $files = $file->find();
            SystemCache::setWithCheckFile($key, $files, $file->getPath());
        }

        foreach($files as &$one){
            $one = new File($one);
        } unset($one);

        $paths = array_merge($paths, $files);

        foreach($paths as $path){
            /** @var $path File */
            $tmp = $path->getName();
            if (!$path->getExtension() || $tmp[0] === '.'){
                $name = $origin = $path->getName();
                $i = 1;
                while(isset(self::$apps[$name])){
                    $name = $origin . '_' . $i;
                    $i++;
                }

                self::$apps[ $name ] = new Application($path);
            }
        }

        if (self::$bootstrap)
            self::$bootstrap->onAfterRegisterApps(self::$apps);
    }

    private static function registerApplication(Application $app, URL $baseUrl){
        register_shutdown_function(array(Regenix::type, '__shutdown'), $app);
        if (self::$bootstrap)
            self::$bootstrap->onBeforeRegisterCurrentApp($app);

        $app->setUriPath( $baseUrl );
        $app->register();
        DI::bind($app);

        if (self::$bootstrap)
            self::$bootstrap->onAfterRegisterCurrentApp($app);
    }

    private static function _registerCurrentAppFromCache(){
        /** @var $request Request */
        $request = DI::getInstance(Request::type);
        $hash = '$rgx.url.' . $request->getHash();

        $app = SystemCache::get($hash, true);
        if (is_string($app)){
            $app = new Application(new File(Application::getApplicationsPath() . $app));
            $app->register();

            if ($app){
                $url = $app->findCurrentPath();
                self::registerApplication($app, $url);
                return true;
            }
        }
        return false;
    }

    private static function _registerCurrentApp(){
        /**
         * @var Application $app
         */
        foreach (self::$apps as $app){
            $url = $app->findCurrentPath();
            if ( $url ){
                Regenix::trace('Current app detected, register it ...');

                if (REGENIX_STAT_OFF){
                    $request = DI::getInstance(Request::type);
                    $hash = '$rgx.url.' . $request->getHash();
                    SystemCache::setId('');
                    SystemCache::set($hash, $app->getName(), 3600, true);
                }

                self::registerApplication($app, $url);
                return;
            }
        }

        throw new HttpException(HttpException::E_NOT_FOUND, "Can`t find an application for the current request");
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return Response
     * @throws HttpException
     * @throws CoreException
     * @throws \Exception|HttpException
     */
    public static function processRequest(Application $app, Request $request){
        $router = $app->router;
        $router->route($request);
        try {
            if (!$router->action){
                throw new HttpException(404, 'Not found');
            }

            // TODO optimize ?
            $tmp = explode('.', $router->action);
            $controllerClass = implode('\\', array_slice($tmp, 0, -1));
            $actionMethod    = $tmp[ sizeof($tmp) - 1 ];

            /** @var $controller Controller */
            $controller = DI::getInstance($controllerClass);

            //$controller = new $controllerClass;

            $controller->actionMethod = $actionMethod;
            $controller->routeArgs    = $router->args;

            try {
                $reflection = new \ReflectionMethod($controller, $actionMethod);
                $controller->actionMethodReflection = $reflection;
            } catch(\ReflectionException $e){
                throw new HttpException(404, $e->getMessage());
            }

            $declClass = $reflection->getDeclaringClass();

            if ( $declClass->isAbstract() ){
                throw new CoreException('Can`t use the "%s.%s()" as action method', $controllerClass, $actionMethod);
            }

            if (self::$bootstrap)
                self::$bootstrap->onBeforeRequest($request);

            SDK::trigger('beforeRequest', array($controller));

            $controller->callBefore();
            Regenix::trace('Process request preparing - finish.');

            $return = $router->invokeMethod($controller, $reflection);

            // if use return statement
            $controller->callReturn($return);

        } catch (Result $result){
            $response = $result->getResponse();
        } catch (\Exception $e){

            if ( $controller ){
                try {
                    if ($e instanceof HttpException){
                        $controller->callHttpException($e);
                        $responseErr = $e->getTemplateResponse();
                    }

                    // if no result, do:
                    $controller->callException($e);
                    if ($app->bootstrap)
                        $app->bootstrap->onException($e);
                } catch (Result $result){
                    /** @var $responseErr Response */
                    $responseErr = $result->getResponse();
                }
            }

            if ( !$responseErr )
                throw $e;
            else {
                $response = $responseErr;
                if ($e instanceof HttpException)
                    $response->setStatus($e->getStatus());
            }
        }

        if ( !$responseErr ){
            $controller->callAfter();
            SDK::trigger('afterRequest', array($controller));
        }

        if (self::$bootstrap)
            self::$bootstrap->onAfterRequest($request);

        if ( !$response ){
            throw new CoreException('Unknown type of action `%s.%s()` result for response',
                $controllerClass, $actionMethod);
        }

        $response->send();
        $controller->callFinally();
        SDK::trigger('finallyRequest', array($controller));

        if (self::$bootstrap)
            self::$bootstrap->onFinallyRequest($request);

        Regenix::trace('Process request finish.');

        return $response;
    }

    private static function catchError($error, $logPath){
        if (self::$bootstrap)
            self::$bootstrap->onError($error);

        $title = 'Fatal Error';

        switch($error['type']){
            case E_PARSE: $title = 'Parse Error'; break;
            case E_COMPILE_ERROR: $title = 'Compile Error'; break;
            case E_CORE_ERROR: $title = 'Core Error'; break;
        }

        $file = str_replace('\\', '/', $error['file']);
        $error['line'] = CoreException::getErrorLine($file, $error['line']);
        $file = $error['file'] = CoreException::getErrorFile($file);
        $file = str_replace(str_replace('\\', '/', ROOT), '', $file);

        $source = null;
        if (REGENIX_IS_DEV && file_exists($error['file']) && is_readable($error['file']) ){
            $fp = fopen($error['file'], 'r');
            $n  = 1;
            $source = array();
            while($line = fgets($fp, 4096)){
                if ( $n > $error['line'] - 7 && $n < $error['line'] + 7 ){
                    $source[$n] = $line;
                }
                if ( $n > $error['line'] + 7 )
                    break;
                $n++;
            }
        }

        $hash = substr(md5(rand()), 12);
        if ($logPath){
            $can = true;
            if (!is_dir($logPath))
                $can = mkdir($logPath, 0777, true);

            if ($can){
                $fp = fopen($logPath . 'fail.log', 'a+');
                $time = date("[Y/M/d H:i:s]");
                fwrite($fp,  "[$hash]$time" . PHP_EOL . "($title): $error[message]" . PHP_EOL);
                fwrite($fp, $file . ' (' . $error['line'] . ')'.PHP_EOL . PHP_EOL);
                fclose($fp);
            }
        }
        include REGENIX_ROOT . 'views/system/errors/fatal.phtml';
    }

    private static function catchAny(\Exception $e){
        $app = Regenix::app();
        if ($app && $app->bootstrap){
            try {
                $app->bootstrap->onException($e);
            } catch (Result $e){
                $e->getResponse()->send();
                return;
            }
        }

        if ( $e instanceof HttpException ){
            $e->getTemplateResponse()->send();
            return;
        }


        $stack = CoreException::findAppStack($e);
        if ($stack === null && REGENIX_DEBUG){
            $stack = current($e->getTrace());
        }
        $info  = new \ReflectionClass($e);

        $description = $e->getMessage();
        $title = $info->getShortName();
        if ($e instanceof CoreException){
            $line = $e->getSourceLine();
            if ($line !== null)
                $stack['line'] = $line;

            $file = $e->getSourceFile();
            if ($file !== null)
                $stack['file'] = $file;

            if ($value = $e->getDescription())
                $description = $value;

            if ($value = $e->getTitle())
                $title = $value;
        }

        if ($stack){
            $file = str_replace('\\', '/', $stack['file']);
            $stack['line']         = CoreException::getErrorLine($file, $stack['line']);
            $file = $stack['file'] = CoreException::getErrorFile($file);

            $file = str_replace(str_replace('\\', '/', ROOT), '', $file);

            $source = null;
            if (file_exists($stack['file']) && is_readable($stack['file']) ){
                $fp = fopen($stack['file'], 'r');
                $n  = 1;
                $source = array();
                while($line = fgets($fp, 4096)){
                    if ( $n > $stack['line'] - 7 && $n < $stack['line'] + 7 ){
                        $source[$n] = $line;
                    }
                    if ( $n > $stack['line'] + 7 )
                        break;
                    $n++;
                }
            }
        }

        $hash = substr(md5(rand()), 12);
        $template = TemplateLoader::load('system/errors/exception.html');

        $template->putArgs(array(
            'exception' => $e instanceof CoreException && $e->isHidden() ? null : $e,
            'stack' => $stack,
            'info' => $info,
            'hash' => $hash,
            'title' => $title,
            'desc' => $description,
            'file' => $file,
            'source' => $source,
            'src' => Regenix::app(),
            'debug_info' => Regenix::getDebugInfo(),
            'controller' => DI::getInstance(Controller::type)
        ));

        Logger::error('%s, in file `%s(%s)`, id: %s', $e->getMessage(), $file ? $file : "nofile", (int)$stack['line'], $hash);

        $response = new Response();
        $response->setStatus(500);
        $response->setEntity($template);
        $response->send();
    }

    public static function catchException(\Exception $e){
        self::catchAny($e);
    }

    public static function getFrameworkPath(){
        return REGENIX_ROOT;
    }

    public static function getTempPath(){
        return self::$rootTempPath . self::$tempPath;
    }

    /**
     * @return bool
     */
    public static function isCLI(){
        return IS_CLI;
    }

    public static function __errorHandler($errno, $errstr, $errfile, $errline){
        if ($errno === E_RECOVERABLE_ERROR){
            throw new WrappedException(new CoreException($errstr), new File($errfile), $errline);
        }

        if ( APP_MODE_STRICT ){
            $app =  Regenix::app();
            $errfile = str_replace('\\', '/', $errfile);

            // only for src sources
            if (!$app || String::startsWith($errfile, $app->getPath())){

                if ( $errno === E_DEPRECATED
                    || $errno === E_USER_DEPRECATED
                    || $errno === E_WARNING
                    || $errno === E_STRICT){

                    throw new CoreStrictException($errstr);
                }

                // ignore tmp dir
                if (!$app || String::startsWith($errfile, $app->getTempPath()) )
                    return false;

                if (String::startsWith($errstr, 'Undefined variable:')
                    || String::startsWith($errstr, 'Use of undefined constant')){
                    throw new CoreStrictException($errstr);
                }
            }
        }
    }

    public static function __shutdown(Application $app){
        $error = error_get_last();
        if ($error){
            switch($error['type']){
                case E_ERROR:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_PARSE:
                case E_USER_ERROR:
                case 4096: // Catchable fatal error
                {
                    self::catchError($error,
                        $app->config->getBoolean('logger.fatal.enable',true)
                            ? $app->getLogPath()
                            : false);

                    break;
                }
            }
        }

        ignore_user_abort(true);

        ob_end_flush();
        ob_flush();
        flush();
    }

    /*** utils ***/
    public static function setTempPath($dir){
        $path = self::$rootTempPath . $dir;
        if ( !is_dir($path) ){
            if ( !mkdir($path, 0777, true) ){
                echo 'Unable to create temp directory `' . $path . '`';
                exit(1);
            }
            @chmod($path, 0777);
        }
        self::$tempPath = str_replace('\\', '/', $dir);
    }
}
