<?php
namespace regenix {

    use regenix\lang\SystemCache;
    use regenix\config\ConfigurationReadException;
    use regenix\config\PropertiesConfiguration;
    use regenix\console\Commander;
    use regenix\deps\Repository;
    use regenix\exceptions\JsonFileException;
    use regenix\exceptions\TypeException;
    use regenix\lang\DI;
    use regenix\lang\FileNotFoundException;
    use regenix\lang\CoreException;
    use regenix\exceptions\CoreStrictException;
    use regenix\exceptions\HttpException;
    use regenix\lang\File;
    use regenix\lang\ClassScanner;
    use regenix\lang\String;
    use regenix\libs\Captcha;
    use regenix\logger\Logger;
    use regenix\modules\Module;
    use regenix\mvc\Controller;
    use regenix\mvc\Request;
    use regenix\mvc\Response;
    use regenix\mvc\Result;
    use regenix\mvc\URL;
    use regenix\mvc\route\Router;
    use regenix\mvc\route\RouterConfiguration;
    use regenix\mvc\session\APCSession;
    use regenix\mvc\template\BaseTemplate;
    use regenix\mvc\template\TemplateLoader;

final class Regenix {

    const type = __CLASS__;

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
        $frameworkDir = str_replace(DIRECTORY_SEPARATOR, '/', realpath(__DIR__)) . '/';
        define('REGENIX_ROOT', $frameworkDir);
        require $frameworkDir . 'lang/PHP.php';

        self::trace('PHP lang file included.');

        set_include_path($rootDir);
        self::$rootTempPath = sys_get_temp_dir() . '/regenix_v' . self::getVersion() . '/';
        unset($_GET, $_REQUEST);


        // register class loader
        ClassScanner::init($rootDir, array(REGENIX_ROOT));
        self::trace('Add class path of framework done.');

        if ($inWeb){
            if (file_exists($globalFile = Application::getApplicationsPath() . '/GlobalBootstrap.php')){
                require $globalFile;
                self::$bootstrap = new \GlobalBootstrap();
                if (!(self::$bootstrap instanceof AbstractGlobalBootstrap))
                    throw new CoreException("Your GlobalBootstrap class should be inherited by AbstractGlobalBootstrap");
            }

            self::_registerTriggers();
            self::_deploy();
            self::trace('Deploy finish.');

            self::_registerApps();
            self::trace('Register apps finish.');

            self::_registerCurrentApp();
            self::trace('Register current finish.');

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

            defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
            define('CONSOLE_STDOUT', fopen('php://stdout', 'w+'));
        }

        if (!REGENIX_IS_DEV)
            error_reporting(0);
    }

    /**
     * Init for web src
     * @param $rootDir
     */
    public static function initWeb($rootDir){
        try {
            self::init($rootDir);

            $app = Regenix::app();
            $request = Request::current();
            $request->setBasePath( $app->getUriPath() );

            self::processRequest($app, $request);
        } catch (\Exception $e){
            if (self::$bootstrap)
                self::$bootstrap->onException($e);

            self::catchException($e);
        }
    }

    /**
     * Add application from external directory
     * @param $path
     * @throws lang\CoreException
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
     * @return \regenix\Application
     */
    public static function app(){
        return Application::current();
    }

    /**
     * Write trace log
     * @param $message
     */
    public static function trace($message){
        self::$profileLog[] =
            array(
                'message' => $message,
                'debug' => array(
                    'time' => (microtime(true) - self::$traceTime) * 1000,
                    'memory' => (memory_get_usage() - self::$traceMemory)
                )
            );

        self::$traceTime   = microtime(true);
        self::$traceMemory = memory_get_usage();
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
        $paths = array_merge($paths, $file->findFiles());

        foreach($paths as $path){
            if ($path->isDirectory()){
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

    private static function _registerCurrentApp(){
        /** 
         * @var Application $app
         */
        foreach (self::$apps as $app){
            $url = $app->findCurrentPath();
            if ( $url ){
                register_shutdown_function(array(Regenix::type, '__shutdown'), $app);
                if (self::$bootstrap)
                    self::$bootstrap->onBeforeRegisterCurrentApp($app);

                $app->setUriPath( $url );
                $app->register();

                if (self::$bootstrap)
                    self::$bootstrap->onAfterRegisterCurrentApp($app);
                return;
            }
        }
        
        throw new HttpException(HttpException::E_NOT_FOUND, "Can`t find an application for the current request");
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return Response
     * @throws exceptions\HttpException
     * @throws lang\CoreException
     * @throws \Exception|exceptions\HttpException
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
            throw new CoreException('Unknown type of action `%s.%s()` result for response', $controllerClass, $actionMethod);
        }

        $response->send();
        $controller->callFinally();
        SDK::trigger('finallyRequest', array($controller));

        if (self::$bootstrap)
            self::$bootstrap->onFinallyRequest($request);

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
        if ($stack === null && IS_CORE_DEBUG){
            $stack = current($e->getTrace());
        }
        $info  = new \ReflectionClass($e);

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

        $template->putArgs(array('exception' => $e,
            'stack' => $stack, 'info' => $info, 'hash' => $hash,
            'desc' => $e->getMessage(),
            'file' => $file,
            'source' => $source,
            'src' => Regenix::app(),
            'debug_info' => Regenix::getDebugInfo(),
            'controller' => Controller::current()
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
        if ( APP_MODE_STRICT ){
            $app =  Regenix::app();
            $errfile = str_replace('\\', '/', $errfile);

            // only for src sources
            if (!$app || String::startsWith($errfile, $app->getPath())){
                if ( $errno === E_DEPRECATED
                    || $errno === E_USER_DEPRECATED
                    || $errno === E_WARNING ){
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


    abstract class AbstractGlobalBootstrap {
        public function onBeforeDeploy(\ZipArchive $zip, $newAppDir){}
        public function onAfterDeploy(\ZipArchive $zip, $newAppDir){}

        public function onBeforeRegisterApps(File &$pathToApps){}
        public function onAfterRegisterApps(&$apps){}

        public function onBeforeRegisterCurrentApp(Application $app){}
        public function onAfterRegisterCurrentApp(Application $app){}

        public function onException(\Exception $e){}
        public function onError(array $error){}

        public function onBeforeRequest(Request $request){}
        public function onAfterRequest(Request $request){}
        public function onFinallyRequest(Request $request){}
    }

    abstract class AbstractBootstrap {

        /** @var Application */
        protected $app;

        public function setApp(Application $app){
            $this->app = $app;
        }

        public function onStart(){}
        public function onEnvironment(&$env){}

        /** @return Response */
        public function onException(\Exception $e){ return null; }
        public function onTest(array &$tests){}
        public function onUseTemplates(){}
        public function onTemplateRender(BaseTemplate $template){}
    }


    /**
     * Class Application
     * @package regenix
     */
    class Application {

        const type = __CLASS__;

        private $name;

        /** @var URL[] */
        private $rules = array();

        /** @var string */
        private $currentPath;

        /** @var string */
        private $mode = 'dev';

        /** @var string */
        public $secret;

        /** @var PropertiesConfiguration */
        public $config;

        /** @var array */
        public $deps;

        /** @var mvc\route\Router */
        public $router;

        /** @var Repository */
        public $repository;

        /** @var AbstractBootstrap */
        public $bootstrap;

        /** @var array */
        protected $assets;

        /** @var File */
        protected $path;

        /**
         * @param lang\File $appPath
         * @param bool $inWeb
         * @internal param string $appName root directory name of src
         */
        public function __construct(File $appPath, $inWeb = true){
            $this->path = $appPath;
            $this->name = $appName = $appPath->getName();

            SystemCache::setId($appName);
            $cacheName = 'app.conf';

            $configFile   = $this->getPath() . 'conf/application.conf';
            $this->config = SystemCache::getWithCheckFile($cacheName, $configFile);

            if ($this->config === null){
                $this->config = new PropertiesConfiguration(new File( $configFile ));
                SystemCache::setWithCheckFile($cacheName, $this->config, $configFile);
                Regenix::trace('Read config for new app - ' . $appName);
            }

            $this->applyConfig( $this->config );
            Regenix::trace('New app - ' . $appName);
        }

        /**
         * get src name (root directory name)
         * @return string
         */
        public function getName(){
            return $this->name;
        }

        /**
         * get src root path
         * @return string
         */
        public function getPath(){
            return $this->path->getPath() . '/';
        }

        public function getSrcPath(){
            return $this->getPath() . 'src/';
        }

        public function getViewPath(){
            return $this->getSrcPath() . 'views/';
        }

        public function getModelPath(){
            return $this->getSrcPath() . 'models/';
        }

        public function getTestPath(){
            return $this->getPath() . 'tests/';
        }

        public function getLogPath(){
            return ROOT . 'logs/' . $this->name . '/';
        }

        public function getTempPath(){
            return Regenix::getTempPath();
        }

        /**
         * get public upload directory
         * @return string
         */
        public function getPublicPath(){
            return ROOT . 'public/' . $this->name . '/';
        }

        /**
         * get public upload uri
         * @return string
         */
        public function getPublicUri(){
            return '/public/' . $this->name . '/';
        }

        /*
         * пути можно указывать с доменами и с портами
         * examples:
         *
         *   domain.com:80/s1/
         *   domain.com:80/s2/
         */
        public function setRules(array $rules){
            foreach(array_unique($rules) as $rule){
                $this->rules[ $rule ] = new URL( $rule );
            }
        }


        /**
         * replace part configuration
         * @param \regenix\config\Configuration|\regenix\config\PropertiesConfiguration $config
         */
        public function applyConfig(PropertiesConfiguration $config){
            $rules = $config->getArray("app.rules", array('/'));
            $this->setRules( $rules );
        }


        /**
         * @return boolean
         */
        public function findCurrentPath(){
            $request = Request::current();
            foreach ($this->rules as $url){
                if ( $request->isBase( $url ) )
                    return $url;
            }

            return null;
        }


        public function setUriPath(URL $url){
            $this->currentPath = $url->getPath();
        }

        public function getUriPath($suffix = ''){
            if ($suffix){
                if ($suffix === '/')
                    return $this->currentPath;
                else
                    return $this->currentPath . $suffix;
            } else
                return $this->currentPath;
        }

        /**
         * @param string $group
         * @param bool $version, if false return last version
         * @throws lang\CoreException
         * @return array
         */
        public function getAsset($group, $version = false){
            $all      = $this->getAssets();
            $versions = $all[$group];

            if (!$versions)
                throw new CoreException('Asset `%s` is not found', $group);

            if ($version){
                $info = $versions[$version];
                if (!is_array($info)){
                    throw new CoreException('Asset `%s/%s` is not found', $group, $version);
                }
            } else {
                list($version, $info) = each($versions);
            }

            $meta = $this->repository->getLocalMeta($group, $version);
            if (!$meta)
                throw new CoreException('Meta information is not found for `%s` asset, run `deps update` to fix it', $group);

            $info['version'] = $version;
            return $info + $meta;
        }

        /**
         * Get files all assets
         * @param string $group
         * @param bool $version
         * @param array $included
         * @return array
         * @throws static
         * @throws FileNotFoundException
         */
        public function getAssetFiles($group, $version = false, &$included = array()){
            $info = $this->getAsset($group, $version);

            if ($included[$group])
                return array();

            /*if ($included[$group][$info['version']])
                return array();*/

            $included[$group][$info['version']] = true;

            $result = array();
            if (is_array($info['deps'])){
                foreach($info['deps'] as $gr => $v){
                    $result = array_merge($result, $this->getAssetFiles($gr, $v, $included));
                }
            }

            $path   = '/assets/' . $group . '~' . $info['version'] . '/';
            foreach((array)$info['files'] as $file){
                $result[] = $path . $file;

                if (REGENIX_IS_DEV && !is_file(ROOT . $path . $file)){
                    throw new FileNotFoundException(new File($path . $file));
                }
            }

            return $result;
        }

        public function isDev(){
            return $this->mode !== 'prod';
        }

        public function isProd(){
            return $this->mode === 'prod';
        }

        public function isMode($mode){
            return $this->mode === $mode;
        }

        public function register($inWeb = true){
            Regenix::trace('.register() application pre-start');

            Application::$instance = $this;
            SystemCache::setId($this->name);

            if (file_exists($boostrap = $this->getSrcPath() . 'Bootstrap.php'))
                require $boostrap;

            if (class_exists('\\Bootstrap')){
                $this->bootstrap = new \Bootstrap();
                $this->bootstrap->setApp($this);
            }

            // config
            $this->mode = strtolower($this->config->getString('app.mode', 'dev'));
            if ($this->bootstrap)
                $this->bootstrap->onEnvironment($this->mode);

            if (!$this->mode)
                throw new CoreException('Application mode should be set in `Bootstrap::onEnvironment()` method or `conf/application.conf` file');

            define('IS_PROD', $this->isProd());
            define('IS_DEV', $this->isDev());
            define('REGENIX_IS_DEV', $this->isDev());
            define('IS_CORE_DEBUG', $this->config->getBoolean('core.debug'));
            define('APP_MODE_STRICT', $this->config->getBoolean('app.mode.strict', IS_DEV));

            define('APP_MODE', $this->mode);
            $this->config->setEnv( $this->mode );

            ClassScanner::addClassPath($inWeb ? $this->getSrcPath() : $this->getPath());

            define('APP_PUBLIC_PATH', $this->config->get('app.public', '/public/' . $this->name . '/'));
            $this->secret = $this->config->getString('app.secret');
            if ( !$this->secret ){
                throw new ConfigurationReadException($this->config, '`app.secret` should be set as a random string');
            }

            Regenix::trace('.register() application start, class path added.');
            // temp
            Regenix::setTempPath( $this->name . '/' );

            $sessionDriver = new APCSession();
            $sessionDriver->register();

            // module deps
            $this->_registerDependencies();
            Regenix::trace('.registerDependencies() application finish');

            // route
            $this->_registerRoute();
            Regenix::trace('.registerRoute() application finish');

            $this->_registerOrm();
            Regenix::trace('.registerOrm() application finish');

            if (REGENIX_IS_DEV)
                $this->_registerTests();

            if ($inWeb){
                $this->_registerSystemController();
                Regenix::trace('.registerSystemController() application, finish register app');
            }

            if ($this->bootstrap){
                $this->bootstrap->onStart();
            }

            Regenix::trace('.registerBootstrap() application, finish register app');
        }

        public function loadDeps(){
            $file = $this->getPath() . 'conf/deps.json';
            $this->deps = array();

            if (is_file($file)){
                if (REGENIX_IS_DEV){
                    $this->deps = json_decode(file_get_contents($file), true);

                    if (json_last_error()){
                        throw new JsonFileException('conf/deps.json');
                    }
                } else {
                    $this->deps = SystemCache::getWithCheckFile('app.deps', $file);
                    if ($this->deps === null){
                        $this->deps = json_decode(file_get_contents($file), true);
                        if (json_last_error()){
                            throw new JsonFileException('conf/deps.json', 'invalid json format');
                        }
                        SystemCache::setWithCheckFile('app.deps', $this->deps, $file, 60 * 5);
                    }
                }
                return true;
            } else
                return false;
        }

        /**
         * Get all assets of app
         *
         * @throws lang\CoreException
         * @return array
         */
        public function getAssets(){
            if (is_array($this->assets))
                return $this->assets;

            $this->assets = $this->repository->getAll('assets');

            if (REGENIX_IS_DEV){
                foreach($this->assets as $group => $versions){
                    foreach($versions as $version => $el){
                        if (!$this->repository->isValid($group, $version)){
                            throw new CoreException('Asset `%s/%s` is not valid or non-exist, please run `regenix deps update` in console', $group, $version);
                        }
                    }
                }
            }
            return $this->assets;
        }

        private function _registerDependencies(){
            $this->loadDeps();
            $this->repository = new Repository($this->deps);

            // modules
            if ($this->deps['modules']){
                $this->repository->setEnv('modules');
                foreach((array)$this->deps['modules'] as $name => $conf){
                    $dep = $this->repository->findLocalVersion($name, $conf['version']);
                    if (!$dep){
                        throw new CoreException('Can`t find the `%s/%s` module, run `regenix deps update` in console to fix it', $name, $conf['version']);
                    } elseif (REGENIX_IS_DEV && !$this->repository->isValid($name, $dep['version'])){
                        throw new CoreException('Module `%s` is not valid or non-exist, run `regenix deps update` in console to fix it', $name);
                    }
                    Module::register($name, $dep['version']);
                }
            }

            if (REGENIX_IS_DEV)
                $this->getAssets();

            if (file_exists($file = $this->getPath() . 'vendor/autoload.php'))
                require $file;
        }

        private function _registerSystemController(){
            if ($this->config->getBoolean('captcha.enable')){
                if (IS_DEV)
                    Captcha::checkAvailable();

                $this->router->addRoute('GET',
                    '/system/captcha.img',
                    '.regenix.mvc.SystemController.captcha'
                );
            }

            if ($this->config->getBoolean('i18n.js')){
                $this->router->addRoute('GET', '/system/i18n.js', '.regenix.mvc.SystemController.i18n_js');
                $this->router->addRoute('GET', '/system/i18n.{_lang}.js', '.regenix.mvc.SystemController.i18n_js');
            }
        }

        private function _registerTests(){
            $this->router->addRoute('*', '/@test', '.regenix.test.Tester.run');
            $this->router->addRoute('GET', '/@test.json', '.regenix.test.Tester.runAsJson');
        }

        private function _registerOrm(){
            if (file_exists($this->getPath() . 'conf/orm/')){
                $file = $this->getPath() . 'conf/orm/build/conf/-conf.php';
                if (file_exists($file)){
                    if (!class_exists('\\Propel'))
                        throw new CoreException('Propel ORM vendor library is not installed');

                    \Propel::init($file);
                } else {
                    throw new CoreException('Cannot find `%s` runtime configuration of Propel ORM, '
                        . "\n\n"
                        . 'Create `conf/orm/runtime-conf.xml` and run in console `regenix propel convert-conf` to fix it',
                        $file);
                }
            }
        }

        private function _registerRoute(){
            // routes
            $routeFile = $this->getPath() . 'conf/route';
            $route = new File($routeFile);
            $this->router = SystemCache::get('route');
            $routePatternDir = new File($this->getPath() . 'conf/routes/');

            $upd = SystemCache::get('routes.$upd');
            if ( $this->router === null
                || $route->isModified($upd, false)
                || $routePatternDir->isModified($upd, REGENIX_IS_DEV) ){

                $this->router = new Router();
                $routeConfig  = new RouterConfiguration();

                foreach (modules\Module::$modules as $name => $module){
                    $routeConfig->addModule($name, '.modules.' . $name . '.controllers.', $module->getRouteFile());
                }

                $routeConfig->setPatternDir($routePatternDir);
                $routeConfig->setFile(new File($routeFile));

                $routeConfig->load();
                $routeConfig->validate();

                $this->router->applyConfig($routeConfig);

                SystemCache::setWithCheckFile('route', $this->router, $routeFile, 60 * 5);
                $upd = $routePatternDir->lastModified(REGENIX_IS_DEV);
                $updR = $route->lastModified();
                if ($updR > $upd)
                    $upd = $updR;

                SystemCache::set('routes.$upd', $upd);
            }
        }

        /** @var Application */
        private static $instance;

        /**
         * @return Application
         */
        public static function current(){
            return self::$instance;
        }

        private static $srcDir = null;
        public static function getApplicationsPath(){
            if ( self::$srcDir ) return self::$srcDir;

            return self::$srcDir = str_replace(DIRECTORY_SEPARATOR, '/', ROOT . 'apps/');
        }
    }


    /**
     * Class SDK
     * @package framework
     */
    abstract class SDK {

        private static $types = array();
        private static $handlers = array();

        private static function setCallable($callback, array &$to, $prepend = false){
            if ( REGENIX_IS_DEV && !is_callable($callback) ){
                throw new TypeException('callback', 'callable');
            }

            if ( $prepend )
                array_unshift($to, $callback);
            else
                $to[] = $callback;
        }

        /**
         * @param string $trigger
         * @param callable $callback
         * @param bool $prepend
         * @throws CoreException
         */
        public static function addHandler($trigger, $callback, $prepend = false){
            if (REGENIX_IS_DEV && !self::$types[$trigger])
                throw new CoreException('Trigger type `%s` is not registered', $trigger);

            if (!self::$handlers[$trigger])
                self::$handlers[$trigger] = array();

            self::setCallable($callback, self::$handlers[$trigger], $prepend);
        }

        /**
         * @param string $name
         * @param array $args
         * @throws lang\CoreException
         */
        public static function trigger($name, array $args = array()){
            if (REGENIX_IS_DEV && !self::$types[$name])
                throw new CoreException('Trigger type `%s` is not registered', $name);

            foreach((array)self::$handlers[$name] as $handle){
                call_user_func_array($handle, $args);
            }
        }

        /**
         * @param string $name
         */
        public static function registerTrigger($name){
            self::$types[$name] = true;
        }

        /**
         * @param string $name
         */
        public static function unregisterTrigger($name){
            unset(self::$types[$name]);
        }

        /**
         * @param string $moduleUID
         * @return bool
         */
        public static function isModuleRegister($moduleUID){
            return (boolean)modules\Module::$modules[ $moduleUID ];
        }
    }
}

namespace {

    define('PHP_TRAITS', function_exists('trait_exists'));

    function dump($var){
        echo '<pre class="_dump">';
        print_r($var);
        echo '</pre>';
    }

    /**
     * get absolute all traits
     * @param $class
     * @param bool $autoload
     * @return array
     */
    function class_uses_all($class, $autoload = true) {
        $traits = array();
        if (!PHP_TRAITS)
            return $traits;

        do {
            $traits = array_merge(class_uses($class, $autoload), $traits);
        } while($class = get_parent_class($class));
        foreach ($traits as $trait => $same) {
            $traits = array_merge(class_uses($trait, $autoload), $traits);
        }
        return array_unique($traits);
    }

    /**
     * check usage trait in object
     * @param $object
     * @param $traitName
     * @param bool $autoload
     * @return bool
     */
    function trait_is_use($object, $traitName, $autoload = true){
        if (!PHP_TRAITS)
            return false;

        $traits = class_uses_all($object, $autoload);
        return isset($traits[$traitName]);
    }
}


