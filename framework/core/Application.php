<?php
namespace regenix\core;

use regenix\core\Regenix;
use regenix\analyze\ApplicationAnalyzeManager;
use regenix\exceptions\FileNotFoundException;
use regenix\lang\SystemCache;
use regenix\config\ConfigurationReadException;
use regenix\config\PropertiesConfiguration;
use regenix\deps\Repository;
use regenix\exceptions\JsonFileException;
use regenix\lang\DI;
use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\lang\ClassScanner;
use regenix\libs\captcha\Captcha;
use regenix\modules\Module;
use regenix\mvc\http\Request;
use regenix\mvc\http\session\drivers\APCSessionDriver;
use regenix\mvc\http\session\drivers\ArraySessionDriver;
use regenix\mvc\http\session\drivers\DefaultSessionDriver;
use regenix\mvc\http\URL;
use regenix\mvc\route\Router;
use regenix\mvc\route\RouterConfiguration;

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

    /** @var Router */
    public $router;

    /** @var Repository */
    public $repository;

    /** @var AbstractBootstrap */
    public $bootstrap;

    /** @var array */
    protected $assets;

    /** @var File */
    protected $path;

    /** @var boolean */
    protected $stat = true;

    /**
     * @param File $appPath
     * @param bool $inWeb
     * @throws \regenix\lang\CoreException
     * @internal param string $appName root directory name of src
     */
    public function __construct(File $appPath, $inWeb = true){
        $this->path = $appPath;
        $this->name = $appName = $appPath->getName();

        SystemCache::setId($appName);
        $cacheName = 'app.conf';

        $configFile = $this->getPath() . 'conf/application.conf';
        if (REGENIX_STAT_OFF){
            $configData = SystemCache::get($cacheName);
        } else
            $configData = SystemCache::getWithCheckFile($cacheName, $configFile);

        if (is_array($configData)){
            $this->config = new PropertiesConfiguration();
            $this->config->addProperties($configData);
        }

        if ($this->config === null){
            $this->config = new PropertiesConfiguration(new File( $configFile ));
            SystemCache::setWithCheckFile($cacheName, $this->config->all(), $configFile);
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

    public function getAssetPath(){
        return $this->getPath() . 'assets/';
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
     * @return string
     */
    public function getMode() {
        return $this->mode;
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
        /** @var $request Request */
        $request = DI::getInstance(Request::type);

        foreach ($this->rules as $url) {
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
     * @throws CoreException
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

        $this->repository->setEnv('assets');
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
        DI::bind($this);

        SystemCache::setId($this->name);

        if (file_exists($bootstrap = $this->getSrcPath() . 'Bootstrap.php')) {
            require $bootstrap;
        }

        if (class_exists('\\Bootstrap')){
            $nameClass = '\\Bootstrap';
            $this->bootstrap = new $nameClass();
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
        define('APP_NAMESPACE', str_replace('.', '\\', $this->config->getString('app.namespace', '')));
        define('APP_NAMESPACE_DOT', str_replace('\\', '.', APP_NAMESPACE));
        define('APP_NAMESPACE_DOT_F', APP_NAMESPACE_DOT ? '.' . APP_NAMESPACE_DOT : '');

        $this->stat = !REGENIX_STAT_OFF;
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

        // session
        if ($inWeb){
            $sessionDriver = new DefaultSessionDriver();
            $sessionDriver->register();

            if (APC_ENABLED && SYSTEM_IN_MEM_CACHED){
                $sessionDriver = new APCSessionDriver();
                $sessionDriver->register();
            }

            Regenix::trace('.register Session Provider done.');
        } else {
            $sessionDriver = new ArraySessionDriver();
            $sessionDriver->register();
        }

        // module deps
        $this->_registerDependencies();
        Regenix::trace('.registerDependencies() application finish');

        if ($this->config->getBoolean('analyzer.enabled', IS_DEV)){
            ClassScanner::addClassPath($this->getTestPath());
            $analyzeManager = new ApplicationAnalyzeManager($this);
            $analyzeManager->analyze();
        }

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
        $this->deps = array();
        if (!$this->stat){
            $this->deps = SystemCache::get('app.deps');
            if ($this->deps !== null){
                return true;
            }
        }

        $file = $this->getPath() . 'conf/deps.json';
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
        } else {
            SystemCache::set('app.deps', $this->deps);
            return false;
        }
    }

    /**
     * Get all assets of app
     *
     * @throws CoreException
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

        if ($this->deps['composer']['require']){
            if ($this->isDev())
                ClassScanner::addClassPath($this->getPath() . 'vendor/');
            else
                include $this->getPath() . 'vendor/autoload.php';
        }
    }

    private function _registerSystemController(){
        if ($this->config->getBoolean('captcha.enabled')){
            if ($this->isDev())
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

    private function loadRouting(RouterConfiguration $routeConfig){
        $this->router = DI::getInstance(Router::type);
        $this->router->applyConfig($routeConfig);
        DI::bind($this->router);
    }

    private function _registerRoute(){
        $routeFile = $this->getPath() . 'conf/route';
        $route = new File($routeFile);
        $routePatternDir = new File($this->getPath() . 'conf/routes/');

        // routes
        $routeConfig = null;
        $routeConfigData = SystemCache::get('route');
        if (is_array($routeConfigData)){
            $routeConfig = new RouterConfiguration();
            $routeConfig->setPatternDir($routePatternDir);
            $routeConfig->setFile($route);
            $routeConfig->addPatterns($routeConfigData);
        }

        // optimize, absolute cache
        if (!$this->stat && $routeConfig !== null){
            $this->loadRouting($routeConfig);
            return;
        }

        $upd = SystemCache::get('routes.$upd');
        if ( !is_array($routeConfig)
            || $route->isModified($upd, false)
            || $routePatternDir->isModified($upd, REGENIX_IS_DEV) ){

            $this->router = DI::getInstance(Router::type);
            $routeConfig  = new RouterConfiguration();

            foreach (Module::$modules as $name => $module){
                $routeConfig->addModule($name, '.modules.' . $name . '.controllers.', $module->getRouteFile());
            }

            $routeConfig->setPatternDir($routePatternDir);
            $routeConfig->setFile($route);

            $routeConfig->load();
            $routeConfig->validate();

            $this->router->applyConfig($routeConfig);

            SystemCache::setWithCheckFile('route', $routeConfig->getRouters(), $routeFile, 60 * 5);
            $upd = $routePatternDir->lastModified(REGENIX_IS_DEV);
            $updR = $route->lastModified();
            if ($updR > $upd)
                $upd = $updR;

            SystemCache::set('routes.$upd', $upd);
        }

        $this->loadRouting($routeConfig);
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
