<?php
namespace regenix\lang {

use regenix\core\Application;
use regenix\core\Regenix;
use regenix\core\SDK;
use regenix\exceptions\ClassNotFoundException;
use regenix\exceptions\FileIOException;
use regenix\exceptions\FileNotOpenException;

if (!defined('T_TRAIT'))
   define('T_TRAIT', 355);

defined('IS_CLI') or define('IS_CLI', isset($_SERVER['argc']));
defined('APC_ENABLED') or define('APC_ENABLED', extension_loaded('apc'));
defined('XCACHE_ENABLED') or define('XCACHE_ENABLED', extension_loaded('xcache'));

if (!defined('SYSTEM_IN_MEM_CACHED')){
    if (defined('IS_CORE_DEBUG') && IS_CORE_DEBUG === true)
        define('SYSTEM_IN_MEM_CACHED', false);
    else {
        if (APC_ENABLED){
            if (IS_CLI){
                define('SYSTEM_IN_MEM_CACHED', false);
            } else {
                define('SYSTEM_IN_MEM_CACHED', !!ini_get('apc.enabled') && XCACHE_ENABLED);
            }
        } else
            define('SYSTEM_IN_MEM_CACHED', !IS_CLI && XCACHE_ENABLED);
    }
}

define('FAST_SERIALIZE_ENABLE', extension_loaded('igbinary'));
defined('SYSTEM_CACHE_TMP_DIR') or define('SYSTEM_CACHE_TMP_DIR', Regenix::getTempPath() .  'syscache/');

/**
 * Class CoreException
 * @package regenix\lang
 */
class CoreException extends \Exception {

    const type = __CLASS__;

    public function __construct($message){
        $args = array();
        if (func_num_args() > 1)
            $args = array_slice(func_get_args(), 1);

        parent::__construct(String::formatArgs($message, $args));
    }

    public function getSourceLine(){
        return null;
    }

    public function getSourceFile(){
        return null;
    }

    public function isHidden(){
        return false;
    }

    public static function findAppStack(\Exception $e){
        if (self::$hideDebug)
            return null;

        $app = Regenix::app();
        if ($app){
            $appDir = str_replace('\\', '/', Application::getApplicationsPath());
            $tmpDir = Regenix::getTempPath();
            $moduleDir  = ROOT . 'modules/';
            foreach($e->getTrace() as $stack){
                $dir = str_replace('\\', '/', dirname($stack['file']));
                if (strpos($dir, $appDir) === 0){
                    return $stack;
                }

                if (strpos($dir, $tmpDir) === 0){
                    return $stack;
                }

                if (self::$onlyPublic) continue;

                if (strpos($dir, $moduleDir) === 0){
                    return $stack;
                }
            }
        }
        return null; //current($e->getTrace());
    }

    private static $files = array();
    private static $offsets = array();

    private static $externalFile = null;
    private static $externalLine = 0;

    /**
     * create error mirror file
     * @param string $original file path
     * @param string $file
     */
    public static function setMirrorFile($original, $file){
        $original = str_replace('\\', '/', $original);
        self::$files[$original] = $file;
    }

    /**
     * @param string $original file path
     * @param int $offset
     */
    public static function setMirrorOffsetLine($original, $offset){
        $original = str_replace('\\', '/', $original);
        self::$offsets[$original] = $offset;
    }

    /**
     * @param $original file path
     * @return string
     * @return mixed|null
     */
    public static function getErrorFile($original){
        if (self::$externalFile)
            return self::$externalFile;

        $original = str_replace('\\', '/', $original);
        if ($file = self::$files[$original])
            return $file;

        return $original;
    }

    /**
     * @param $origin
     * @param $line
     * @return int
     */
    public static function getErrorLine($origin, $line){
        if (self::$externalLine)
            return self::$externalLine;

        return self::getErrorOffsetLine($origin) + $line;
    }

    /**
     * @param $original file path
     * @return int
     */
    public static function getErrorOffsetLine($original){
        $original = str_replace('\\', '/', $original);
        if ($offset = self::$files[$original])
            return (int)$offset;

        return 0;
    }

    /**
     * @param $file
     * @param $line
     */
    public static function setExternal($file, $line = null){
        self::$externalFile = $file;
        self::$externalLine = $line;
    }

    private static $onlyPublic = true;
    private static $hideDebug = false;

    /**
     * @param bool $value
     */
    public static function showOnlyPublic($value){
        self::$onlyPublic = $value;
    }

    public static function hideExceptionDebugInfo(){
        self::$hideDebug = true;
    }

    /**
     * @return bool
     */
    public static function isOnlyPublic(){
        return self::$onlyPublic;
    }
}



interface IClassInitialization {

    const IClassInitialization_type = __CLASS__;

    public static function initialize();
}

final class ClassMetaInfo {

    private $name;
    private $info;

    public function __construct($className, $info){
        $this->info = $info;
        $this->name = $className;
    }

    protected function updateMeta(){
        $scanner = new ClassFileScanner($this->getFilename());
        $meta = $scanner->getMeta();
        $this->info = $meta[$this->getName()];
    }

    public function getName(){
        return $this->name;
    }

    public function getFilename(){
        return $this->info[0];
    }

    /**
     * @return File
     */
    public function getFile(){
        return new File($this->getFilename());
    }

    /**
     * will return true if extends of implements by $interfaceOrClass
     * @param string $interfaceOrClass
     * @return bool
     */
    public function isParentOf($interfaceOrClass){
        if ($interfaceOrClass === $this->getName())
            return true;

        $childrens = $this->getChildrensAll();
        foreach($childrens as $name => $el){
            if ($name === $interfaceOrClass)
                return true;
        }

        $implements = $this->getImplementsAll();
        foreach($implements as $name => $el){
            if ($name === $interfaceOrClass)
                return true;
        }

        return false;
    }

    /**
     * @param $parentClass
     * @return bool
     */
    public function isChildOf($parentClass){
        $info = ClassScanner::find($parentClass);
        if ($info != null)
            return $info->isParentOf($this->getName());
        else
            return false;
    }

    /**
     * @return bool
     */
    public function isClass(){
        return !$this->info[1] || $this->info[1] === T_CLASS;
    }

    /**
     * @return bool
     */
    public function isInterface(){
        if (!isset($this->info[1]))
            $this->updateMeta();

        return $this->info[1] === T_INTERFACE;
    }

    /**
     * @return bool
     */
    public function isTrait(){
        if (!isset($this->info[1]))
            $this->updateMeta();

        return $this->info[1] === T_TRAIT;
    }

    /**
     * @return bool
     */
    public function isAbstract(){
        if ($this->isClass()){
            $reflect = new \ReflectionClass($this->name);
            return $reflect->isAbstract();
        } else
            return false;
    }

    /**
     * @param array $args
     * @return object
     */
    public function newInstance($args = array()){
        if ($args){
            $reflect = new \ReflectionClass($this->name);
            return $reflect->newInstanceArgs($args);
        } else {
            $name = $this->name;
            return new $name();
        }
    }

    /**
     * @return ClassMetaInfo
     */
    public function getParent(){
        return ClassScanner::find($this->info[2]);
    }

    /**
     * @return ClassMetaInfo[]
     */
    public function getImplements(){
        if ($items = $this->info[3]){
            foreach($items as &$el){
                $el = ClassScanner::find($el);
            }
            unset($el);
            return $items;
        } else
            return array();
    }

    /**
     * @param array $result
     * @return \regenix\lang\ClassMetaInfo[]
     * @return ClassMetaInfo[]
     */
    public function getImplementsAll(&$result = null){
        $implements = $this->getImplements();
        if ($result === null)
            $result = $implements;

        foreach($implements as $meta){
            $result = $result + $meta->getImplementsAll($result);
        }

        return (array)$result;
    }

    /**
     * @param string $namespace
     * @throws CoreException
     * @return ClassMetaInfo[]
     */
    public function getChildrens($namespace = ''){
        if ($items = $this->info[255]){
            if ($namespace && !String::endsWith($namespace, '\\'))
                $namespace .= '\\';

            $result = array();
            foreach($items as $className => $el){
                if (!$namespace || String::startsWith($className, $namespace)){
                    $result[$className] = ClassScanner::find($className);
                }
            }

            return $result;
        } else
            return array();
    }

    /**
     * Find childrens recursive
     * @param string $namespace
     * @param null $result
     * @return ClassMetaInfo[]
     */
    public function getChildrensAll($namespace = '', &$result = null){
        $childrens = $this->getChildrens($namespace);
        if ($result === null){
            $result = $childrens;
        } else {
            $result = array_merge($result, $childrens);
        }

        foreach($childrens as $child){
            $result = array_merge($result, $child->getChildrensAll($namespace, $result));
        }
        return $result;
    }

    /**
     * @param $namespace
     * @return bool
     */
    public function isNamespace($namespace){
        if (!String::endsWith($namespace, '\\'))
            $namespace .= '\\';

        return String::startsWith($this->name, $namespace);
    }

    public function isLoaded(){
        return class_exists($this->name, false);
    }

    public function load($force = false){
        $class = $this->name;

        if ($force || !class_exists($class, false)){
            if ($file = $this->getFilename()){
                if ($file[0] !== '/' && $file[1] !== ':')
                    require ROOT . $file;
                else
                    require $file;

                $implements = class_implements($class);
                if ( $implements[IClassInitialization::IClassInitialization_type] ){
                    $class::initialize();
                }
            }
        }
    }
}

/**
 * Meta item of one class:
 *
 *      - file
 *      - class parent
 *      - childrens
 *      - namespace (dynamic)
 *
 * Class ClassScanner
 * @package regenix\lang
 */
class ClassScanner {

    /** @var bool */
    protected static $debug = false;

    /** @var array */
    protected static $debugUseClasses = array();

    /** @var string */
    protected static $includePath;

    /** @var array */
    protected static $paths = array();

    /** @var array */
    protected static $ignorePaths = array();

    /** @var array */
    protected static $onlyMeta = array();

    /** @var array */
    protected static $scanned = array();

    /** @var array */
    protected static $extensions = array('php');

    /** @var array */
    public static $metaInfo = array();

    /**
     * @param string $className
     * @return ClassMetaInfo
     */
    public static function find($className){
        if ($className[0] === '\\')
            $className = substr($className, 1);

        if ($meta = self::$metaInfo[$className]){
            if ($meta[0])
                return new ClassMetaInfo($className, $meta);
            else
                return null;
        } else
            return null;
    }

    private static function compressMeta(&$metaInfo, $onlyMeta = array()){
        if (!$onlyMeta)
            return;

        $newMetaInfo = array();
        foreach($metaInfo as $class => $meta){
            $compress = true;
            foreach($onlyMeta as $onlyClass => $el){
                $info = ClassScanner::find($onlyClass);
                if ($onlyClass === $class || ($info && $info->isParentOf($class))){
                    $compress = false;
                    break;
                }
            }

            if ($compress){
                unset($meta[255], $meta[1], $meta[2], $meta[3]);
                unset($meta[3]);
            }

            if ($meta){
                $newMetaInfo[$class] = $meta;
            }
            continue;
        }
        $metaInfo = $newMetaInfo;
    }

    /**
     * run scan procedure
     */
    public static function scan($cached = true){
        if (sizeof(self::$paths) === 1){
            $path = current(self::$paths);
            $hash    = sha1($path);

            $meta = $cached ?
                (REGENIX_IS_DEV
                    ? SystemCache::getWithCheckFile('lang.sc.m.' . $hash, $path, true)
                    : SystemCache::get('lang.sc.m.' . $hash, true)
                ) : null;

            if ($meta === null){
                self::scanPath($path);
                if ($cached){
                    self::compressMeta(self::$metaInfo, self::$onlyMeta);
                    SystemCache::setWithCheckFile('lang.sc.m.' . $hash, self::$metaInfo, $path, 3600, true);
                }
            } else {
                self::$metaInfo = $meta;
            }
            self::$scanned[$path] = true;
        } else
        foreach(self::$paths as $path){
            $hash    = sha1($path);
            if (self::$scanned[$path])
                continue;

            if (REGENIX_IS_DEV === true){
                $results = $cached ? SystemCache::getIf('lang.sc.' . $hash, function() use ($path){
                    $upd = SystemCache::get('lang.sc.$upd', true);
                    if (!$upd)
                        return false;

                    $file = new File($path);
                    return !$file->isModified($upd, true);
                }, true) : null;
            } else {
                $results = $cached ? SystemCache::getWithCheckFile('lang.sc.' . $hash, $path, true) : null;
            }

            if ($results === null){
                $results = self::scanPath($path);
                if ($cached){
                    SystemCache::setWithCheckFile('lang.sc.' . $hash, $results, $path, 3600, true);
                }
            } else {
                foreach($results as $className => $meta){
                    self::addClassMeta($className, $meta);
                }
            }
            self::$scanned[$path] = true;
        }
    }

    /**
     * Clear all meta information
     */
    public static function clear(){
        unset(self::$metaInfo);
        self::$metaInfo = array();
        self::$scanned  = array();
    }

    /**
     * @param string $path
     */
    public static function setIncludePath($path){
        self::$includePath = $path;
    }

    /**
     * Add path with php files for scan
     * @param $path
     * @param bool $scan
     */
    public static function addClassPath($path, $scan = true){
        $path = str_replace(array('\\', '//', '///', '////'), '/', $path);
        if (substr($path, -1) !== '/')
            $path .= '/';

        self::$paths[$path] = $path;

        if ($scan)
            self::scan();
    }

    /**
     * Add path to ignore when scans classes
     * @param $path
     */
    public static function addIgnorePath($path){
        $path = str_replace(array('\\', '//', '///', '////'), '/', $path);
        if (substr($path, -1) !== '/')
            $path .= '/';

        self::$ignorePaths[$path] = $path;
    }

    /**
     * Collect meta information for only $class and other in $onlyMeta
     * @param $class
     */
    public static function addOnlyMetaFor($class){
        $class = str_replace('.', '\\', $class);
        if ($class[0] === '\\')
            $class = substr($class, 1);

        self::$onlyMeta[$class] = true;
    }

    /**
     * Add path with include path
     * @param $path
     * @param bool $scan
     */
    public static function addClassRelativePath($path, $scan = true){
        self::addClassPath(self::$includePath . $path, $scan);
    }

    /**
     * @param string $extension
     */
    public static function addSourceExtension($extension){
        self::$extensions[$extension] = $extension;
    }

    /**
     * @return array
     */
    public static function getClassPaths(){
        return self::$paths;
    }

    /**
     * @param string $extension
     */
    public static function removeSourceExtension($extension){
        unset(self::$extensions[$extension]);
    }

    protected static function addClassMeta($className, $meta){
        $result = array();
        $result[0] = str_replace(self::$includePath, '', $meta[0]);

        // type
        if ($meta[1] !== T_CLASS)
            $result[1] = $meta[1];

        // extends
        if ($extends = $meta[2])
            $result[2] = $meta[2];

        // implements
        if ($implements = $meta[3]){
            $result[3] = $meta[3];
        }

        $metaInfo =& self::$metaInfo;

        if ($childs = $metaInfo[$className][255])
            $result[255] = $childs;

        // if exists parent...
        if ($extends || $implements){
            if (!$implements)
                $implements = array();

            if ($extends){
                $implements[] = $extends;
            }

            foreach($implements as $extend){
                if (!$metaInfo[$extend])
                    $metaInfo[$extend] = array();

                $metaInfo[$extend][255][$className] = 1;
            }
        }

        return $metaInfo[$className] = $result;
    }

    /**
     * scan one path
     * @param string $path
     * @return array
     */
    protected static function scanPath($path){
        $result = array();
        if (self::$ignorePaths[$path]){
            return $result;
        }

        foreach(self::$extensions as $extension){
            foreach(glob($path . '*.' . $extension) as $file){
                $result = $result + self::scanFile($file);
            }
        }

        if (is_file($path . 'classpath.ignore')){
            $lines = file($path . 'classpath.ignore');
            foreach($lines as $line){
                $line = trim($line);
                if ($line){
                    self::addIgnorePath($path . '/' . $line);
                }
            }
        }

        if (is_file($path . 'classpath.meta')){
            $lines = file($path . 'classpath.meta');
            foreach($lines as $line){
                $line = trim($line);
                if ($line){
                    self::addOnlyMetaFor($line);
                }
            }
        }

        foreach(scandir($path) as $file){
            if ($file == '.' || $file == '..') continue;
            if (is_dir($file = $path . $file)){
                $result = $result + self::scanPath($file . '/');
            }
        }

        return $result;
    }

    protected static function scanFile($file){
        $scanner = new ClassFileScanner($file);
        $result  = $scanner->getMeta();

        foreach($result as $className => $meta){
            self::addClassMeta($className, $meta);
        }
        unset($scanner);
        return $result;
    }

    protected static function autoLoadHandler($class){
        $meta = self::find($class);
        if ($meta){
            $meta->load(true);
            if (self::$debug){
                self::$debugUseClasses[] = $meta->getFilename();
            }

            return true;
        } else
            return false;
    }

    public static function registerAutoLoader(){
        spl_autoload_register(array(__CLASS__, 'autoLoadHandler'));
    }

    public static function unregisterAutoLoader(){
        spl_autoload_unregister(array(__CLASS__, 'autoLoadHandler'));
    }

    public static function init($rootPath, array $classPaths = array()){
        self::unregisterAutoLoader();
        self::registerAutoLoader();

        self::setIncludePath($rootPath);

        foreach($classPaths as $classPath){
            self::addClassPath($classPath);
        }
    }

    /**
     * @param string $class
     * @throws ClassNotFoundException
     */
    public static function loadClass($class){
        if ($class && !class_exists($class)){
            throw new ClassNotFoundException($class);
        }
    }

    /**
     * @param bool $debug
     */
    public static function setDebug($debug){
        self::$debug = $debug;
    }

    /**
     * @throws CoreException
     * @return array
     */
    public static function getDebugUseClasses(){
        if (!self::$debug)
            throw new CoreException('Unable to get a list of classes, it cannot be used in debug mode');

        return self::$debugUseClasses;
    }
}

class ClassFileScanner {

    /** @var string */
    protected $file;

    /** @var string */
    protected $source;

    /** @var array */
    protected $tokens;

    /** @var array */
    protected $meta;


    /** @var array */
    protected $token;

    /** @var string */
    private $_namespace;

    /** @var array */
    private $_uses;

    /** @var int */
    private $cursor = 0;

    public function __construct($file){
        $this->file = $file;
        $this->source = file_get_contents($file);
    }

    public function getMeta(){
        $this->parse();
        return $this->meta;
    }

    protected function parse(){
        $this->meta   = array();
        $this->tokens = token_get_all($this->source);
        $this->cursor = 0;
        $size = sizeof($this->tokens);

        while($this->cursor < $size){
            $this->parseToken();
            $this->nextToken();
        }

        unset($this->tokens);
    }

    protected function nextToken(){
        $this->cursor += 1;
        if ($this->cursor >= sizeof($this->tokens)){
            $this->cursor = sizeof($this->tokens);
            return $this->token = null;
        } else {
            return $this->token = $this->tokens[$this->cursor];
        }
    }

    protected function nextTokenName(){
        $next = $this->nextToken();
        $name = '';
        while($next != null && ($next[0] === T_NS_SEPARATOR || $next[0] == T_STRING)){
            $name .= $next[1];
            $next = $this->nextToken();
        }
        return $name;
    }

    protected function nextTokenToType($type){
        do {
            $next = $this->nextToken();
        } while($next != null && $next[0] !== $type && $next !== ',' && $next !== '{');
        if (is_string($next))
            return null;

        return $next;
    }

    protected function prevToken(){
        $this->cursor -= 1;
        if ($this->cursor < 0){
            $this->cursor = -1;
            return $this->token = null;
        } else {
            return $this->token = $this->tokens[$this->cursor];
        }
    }

    protected function addMeta($className, $parentClassName, $implements = array(), $type = T_CLASS){
        if ($className){
            $className = ($this->_namespace ? $this->_namespace . '\\' : '') . $className;
            $result = array($this->file, $type);

            if ($parentClassName)
                $result[2] = $this->getRealClassName($parentClassName);

            if ($implements){
                $result[3] = array_map(array($this, 'getRealClassName'), $implements);
            }

            $this->meta[$className] = $result;
        }
    }

    protected function addUse($use, $as = ''){
        if (!$as){
            $tmp = explode('\\', $use);
            $as  = array_pop($tmp);
        }

        $this->_uses[$as] = $use;
    }

    protected function getRealClassName($class){
        if ($class[0] === '\\'){
            return substr($class, 1);
        }

        if ($real = $this->_uses[$class])
            return $real;

        if ($this->_namespace)
            return $this->_namespace . '\\' . $class;

        return $class;
    }

    protected function parseToken(){
        $token = $this->token;
        switch($token[0]){
            case T_NAMESPACE: {
                $this->nextToken(); // skip whitespace
                $this->_namespace = $this->nextTokenName();
            } break;
            case T_USE: {
                $this->nextToken(); // skip whitespace

                // parse multiple use with ','
                while(true){
                    $name = $this->nextTokenName();
                    $next = $this->nextToken();

                    if ($next[0] === T_AS){
                        $this->nextToken(); // skip whitespace
                        $as = $this->nextTokenName();
                        $this->addUse($name, $as);
                    } else {
                        $this->addUse($name);
                    }

                    if ($this->token === ','){
                        $this->nextTokenToType(T_STRING);
                        $this->prevToken();
                    } else {
                        break;
                    }
                }
            } break;
            case T_TRAIT:
            case T_INTERFACE:
            case T_CLASS: {
                $this->nextToken(); // skip whitespace

                $next = $this->nextToken();
                $className = $next[1];

                $parentClassName = '';
                $implements      = array();
                if ($token[0] === T_CLASS){
                    $cursor = $this->cursor;
                    if($this->nextTokenToType(T_EXTENDS)){
                        $this->nextToken(); // skip whitespace
                        $parentClassName = $this->nextTokenName();
                    } else {
                        $this->cursor = $cursor;
                    }
                }

                $cursor = $this->cursor;
                $next = $this->nextTokenToType(T_IMPLEMENTS);
                if ($next === 0){
                    $this->cursor = $cursor;
                }

                if ($token[0] !== T_TRAIT && $next[0] === T_IMPLEMENTS){
                    $this->nextToken(); // skip ...
                    while(true){
                        $implements[] = $this->nextTokenName();

                        if ($this->nextToken() === ','){
                            $this->nextTokenToType(T_WHITESPACE);
                            $this->prevToken();
                        } else {
                            $this->prevToken();
                            break;
                        }
                    }

                }

                $this->addMeta($className, $parentClassName, $implements, $token[0]);
            } break;
        }
    }
}

final class String {

    const type = __CLASS__;

    private function __construct(){}

    /**
     * @param string $string
     * @return string
     */
    public static function format($string){
        $args = func_get_args();
        return vsprintf($string, array_slice($args, 1));
    }

    /**
     * @param string $string
     * @param array $args
     * @return string
     */
    public static function formatArgs($string, array $args = array()){
        return vsprintf($string, $args);
    }

    /**
     * @param $string
     * @param $from
     * @param null $to
     * @return string
     */
    public static function substring($string, $from, $to = null){
        if ($to === null)
            return substr($string, $from);
        else
            return substr($string, $from, $to - $from);
    }

    /**
     * return true if sting start with
     * @param string $string
     * @param string $with
     * @return boolean
     */
    public static function startsWith($string, $with){
        return strpos($string, $with) === 0;
    }

    /**
     *
     * @param string $string
     * @param string $with
     * @return boolean
     */
    public static function endsWith($string, $with){
        // TODO optimize ?
        return substr($string, -strlen($with)) === $with;
    }

    private static $alpha   = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private static $numeric = '0123456789';
    private static $symbol  = '~!@#$%^&*+/-*_=';

    /**
     * @param int $length
     * @param bool $withNumeric
     * @param bool $withSpecSymbol
     * @return string
     */
    public static function random($length, $withNumeric = true, $withSpecSymbol = false){
        $characters = self::$alpha;
        if ($withNumeric)
            $characters .= self::$numeric;
        if ($withSpecSymbol)
            $characters .= self::$symbol;

        $randomString = '';
        $len = strlen($characters);
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[mt_rand(0, $len - 1)];
        }
        return $randomString;
    }

    /**
     * @param $lengthFrom
     * @param $lengthTo
     * @param bool $withNumeric
     * @param bool $withSpecSymbol
     * @return string
     */
    public static function randomRandom($lengthFrom, $lengthTo, $withNumeric = true, $withSpecSymbol = false){
        $length = mt_rand($lengthFrom, $lengthTo);
        return self::random($length, $withNumeric, $withSpecSymbol);
    }
}


class ArrayTyped implements \Iterator {

    protected $data;

    /**
     * @param array $data
     */
    public function __construct(array $data = null){
        $this->data = $data == null ? array() : $data;
    }

    /**
     * get value by '_arg' key
     * @param mixed $def
     * @return mixed
     */
    public function getDefault($def = null){
        return $this->get('_arg', $def);
    }

    /**
     * @param string $name
     * @return boolean
     */
    public function has($name){
        return isset($this->data[$name]);
    }

    /**
     * @param string $name
     * @param mixed $def
     * @return mixed
     */
    public function get($name, $def = null){
        $value = $this->data[$name];
        return isset($value) ? $value : $def;
    }

    /**
     * return all keys of array
     * @return array
     */
    public function getKeys(){
        return array_keys($this->data);
    }

    /**
     * @param string $name
     * @param string $def
     * @return string
     */
    public function getString($name, $def = ''){
        $value = $this->data[$name];
        return isset($value) ? (string)$value : (string)$def;
    }

    /**
     * @param string $name
     * @param bool $def
     * @return bool
     */
    public function getBoolean($name, $def = false){
        $value = $this->data[$name];
        return isset($value) ?
            $value !== '' && $value !== '0' && $value !== 'false'
            : (boolean)$def;
    }

    /**
     * @param string $name
     * @param int $def
     * @return int
     */
    public function getInteger($name, $def = 0){
        $value = $this->data[$name];
        return isset($value) ? (int)$value : (int)$def;
    }

    /**
     * @param string $name
     * @param float $def
     * @return float
     */
    public function getDouble($name, $def = 0.0){
        $value = $this->data[$name];
        return isset($value) ? (double)$value : (double)$def;
    }

    public function current(){
        return current($this->data);
    }

    public function next(){
        next($this->data);
    }

    public function key(){
        return key($this->data);
    }

    public function valid(){
        return key($this->data) !== null;
    }

    public function rewind(){
        reset($this->data);
    }
}

abstract class StrictObject {

    public function __set($name, $value){
        throw new CoreException('Property `%s` is not defined in `%s` class', $name, get_class($this));
    }

    public function __get($name){
        throw new CoreException('Property `%s` is not defined in `%s` class', $name, get_class($this));
    }
}



/**
 * Class File
 * @package regenix\lang
 */
class File extends StrictObject {

    const type = __CLASS__;

    private $path;
    private $extension;

    /**
     * @param string $path
     * @param \regenix\lang\File $parent
     */
    public function __construct($path, File $parent = null){
        if ( $parent != null ){
            $this->path = $parent->getPath() . $path;
        } else
            $this->path = $path;
    }

    /**
     * @param null|string $prefix
     * @return File
     */
    public static function createTempFile($prefix = null){
        if ($prefix === null){
            $prefix = String::random(5);
        }

        $path = tempnam(sys_get_temp_dir(), $prefix);
        return new File($path);
    }

    /**
     * @param $handle
     * @return File
     */
    public static function createFromHandle($handle){
        $file = new File(null);
        $file->handle = $handle;
        return $file;
    }

    /**
     * @param File $baseFile
     * @return mixed
     */
    public function getRelativePath(File $baseFile){
        $base = str_replace(array('\\', '////', '///', '//'), '/', $baseFile->getPath());
        $path = str_replace(array('\\', '////', '///', '//'), '/', $this->path);

        if (String::startsWith($path, $base)){
            return substr($path, strlen($base));
        } else
            return $path;
    }

    /**
     * get basename of file
     * @param null $suffix
     * @return string
     */
    public function getName($suffix = null){
        return basename( $this->path, $suffix );
    }

    /**
     * get basename of file without ext
     * @return string
     */
    public function getNameWithoutExtension(){
        return $this->getName('.' . $this->getExtension());
    }

    /**
     * get original path of file
     * @return string
     */
    public function getPath(){
        return $this->path;
    }

    /**
     * get parent directory as file object
     * @return File
     */
    public function getParentFile(){
        return new File(dirname($this->path));
    }

    /**
     * get parent directory as string
     * @return string
     */
    public function getParent(){
        return dirname($this->path);
    }

    /**
     * get real path of file
     * @return string
     */
    public function getAbsolutePath(){
        return realpath( $this->path );
    }

    /**
     * get real path as file object
     * @return File
     */
    public function getAbsoluteFile(){
        return new File($this->getAbsolutePath());
    }

    /**
     * get file extension in lower case
     * @return string file ext
     */
    public function getExtension(){
        if ( $this->extension !== null)
            return $this->extension;

        $p = strrpos( $this->path, '.' );
        if ( $p === false )
            return $this->extension = '';

        return $this->extension = (substr( $this->path, $p + 1 ));
    }

    /**
     * return true if file has passed extension
     * @param $extension
     * @return bool
     */
    public function hasExtension($extension){
        if ($extension[0] === '.')
            $extension = substr($extension, 1);

        return strtolower($extension) === $this->getExtension();
    }

    /**
     * return true if one of extensions is extension of file
     * @param array $extensions
     * @return bool
     */
    public function hasExtensions(array $extensions){
        foreach($extensions as $extension){
            if ($this->hasExtension($extension))
                return true;
        }
        return false;
    }

    /**
     * check file exists
     * @return boolean
     */
    public function exists(){
        return file_exists($this->path);
    }

    /**
     * @return boolean
     */
    public function canRead(){
        return is_readable($this->path);
    }

    /**
     * @return bool
     */
    public function isFile(){
        return is_file($this->path);
    }

    /**
     * @return bool
     */
    public function isDirectory(){
        return is_dir($this->path);
    }

    /**
     * @param File $new
     * @return bool
     */
    public function renameTo(File $new){
        return rename($this->getAbsolutePath(), $new->getAbsolutePath());
    }

    /**
     * recursive create dirs
     * @return boolean - create dir
     */
    public function mkdirs(){
        if ( $this->exists() )
            return false;

        return mkdir($this->path, 0777, true);
    }

    /**
     * non-recursive create dirs
     * @return boolean
     */
    public function mkdir(){
        if ( $this->exists() )
            return false;

        return mkdir($this->path, 0777, false);
    }

    /**
     * @return integer get file size in bytes
     */
    public function length(){
        if ( $this->isFile() ){
            return filesize($this->path);
        } else
            return -1;
    }

    /**
     * Get last modified of file or directory in unix time format
     * @param bool $absolute
     * @return int unix time
     */
    public function lastModified($absolute = true){
        if ($absolute && $this->isDirectory()){
            $files  = $this->findFiles();
            $result = filemtime($this->path);
            foreach($files as $file){
                $m_time = $file->lastModified();
                if ($m_time > $result)
                    $result = $m_time;
            }
            return $result;
        } else
            return filemtime($this->path);
    }

    /**
     * Check file or directory modified by current time stamp
     * @param int $currentTime unix time
     * @param bool $absolute
     * @return bool
     */
    public function isModified($currentTime, $absolute = true){
        if ($absolute && $this->isDirectory()){
            $result = filemtime($this->path);
            if ($result > $currentTime)
                return true;

            $files  = $this->findFiles();
            foreach($files as $file){
                $m_time = $file->lastModified();
                if ($m_time > $currentTime)
                    return true;
            }
            return false;
        } else
            return $currentTime < filemtime($this->path);
    }

    /**
     * Delete file or recursive remove directory
     * @return bool
     */
    public function delete(){
        if (is_dir($this->path)){
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileInfo) {
                $todo = ($fileInfo->isDir() ? 'rmdir' : 'unlink');
                @$todo($fileInfo->getRealPath());
            }
            @rmdir($this->path);
        } else {
            @unlink($this->path);
        }
        return !file_exists($this->path);
    }

    private $handle = null;

    /**
     * @param string $mode
     * @throws FileIOException
     * @throws CoreException
     * @return resource
     */
    public function open($mode){
        if ($this->handle)
            throw new CoreException('File "%s" is already opened, close the file before opening', $this->getPath());

        $handle = fopen($this->path, $mode);
        if (!$handle)
            throw new FileIOException($this);

        $this->handle = $handle;
        return $handle;
    }

    /**
     * @param int $length
     * @return string
     * @throws FileNotOpenException
     * @return string
     */
    public function gets($length = 4096){
        if ($this->handle)
            return fgets($this->handle, $length);
        else
            throw new FileNotOpenException($this);
    }

    /**
     * @param int $length
     * @return string
     * @throws FileNotOpenException
     * @return string
     */
    public function read($length = 4096){
        if ($this->handle)
            return fread($this->handle, $length);
        else
            throw new FileNotOpenException($this);
    }

    /**
     * Open file and read all lines with callback
     * @param $callback
     * @param int $length
     * @throws \regenix\exceptions\FileNotOpenException
     */
    public function readLines($callback, $length = 4096){
        $this->open('r+');
        while (($buffer = $this->gets($length)) !== false) {
            call_user_func($callback, $buffer);
        }
        $this->close();
    }

    /**
     * @param $data
     * @param null $length
     * @return int
     * @throws FileNotOpenException
     */
    public function write($data, $length = null){
        if ($this->handle)
            // wtf php ?
            return $length !== null
                ? fwrite($this->handle, (string)$data, $length)
                : fwrite($this->handle, (string)$data);
        else
            throw new FileNotOpenException($this);
    }

    /**
     * @param $offset
     * @param int $whence
     * @return int
     * @throws FileNotOpenException
     */
    public function seek($offset, $whence = SEEK_SET){
        if ($this->handle)
            return fseek($this->handle, (int)$offset, $whence);
        else
            throw new FileNotOpenException($this);
    }

    /**
     * @return bool
     * @throws FileNotOpenException
     */
    public function isEof(){
        if ($this->handle)
            return feof($this->handle);
        else
            throw new FileNotOpenException($this);
    }

    /**
     * @throws FileNotOpenException
     * @return bool
     */
    public function close(){
        if ($this->handle)
            return fclose($this->handle);
        else
            throw new FileNotOpenException($this);
    }

    /**
     * @param null|int $flags
     * @return string
     */
    public function getContents($flags = null){
        if ($this->exists() && $this->isFile())
            return file_get_contents($this->path, $flags);
        else
            return '';
    }

    /**
     * @param $content
     * @param string $mode
     * @return int
     */
    public function putContents($content, $mode = 'w'){
        $this->open($mode);
        $result = $this->write($content);
        $this->close();
        return $result;
    }

    /**
     * @param null|int $time
     * @param null|int $atime
     * @return bool
     */
    public function touch($time = null, $atime = null){
        return touch($this->path, $time, $atime);
    }


    /**
     * find files and dirs
     * @return string[]
     */
    public function find(){
        $path = $this->path;
        if (substr($path, -1) !== '/')
            $path .= '/';

        $files = scandir($path);
        $result = array();
        foreach($files as $file){
            if ($file != '..' && $file != '.')
                $result[] = $path . $file;
        }
        return $result;
    }

    /**
     * @param bool $recursive
     * @return File[]
     */
    public function findFiles($recursive = false){
        $files = $this->find();
        if ($files){
            foreach($files as &$file){
                $file = new File($file);
            }
            unset($file);
        }
        if ($recursive){
            $addFiles = array();
            /** @var $file File */
            foreach($files as $file){
                if ($file->isDirectory()){
                    $addFiles = array_merge($addFiles, $file->findFiles(true));
                }
            }
            $files = array_merge($files, $addFiles);
        }
        return $files;
    }

    public function __toString() {
        return String::format( 'io\\File("%s")', $this->path );
    }

    public static function sanitize($fileName){
        return preg_replace('/[^a-zA-Z0-9-_\.]/', '', $fileName);
    }
}

################ CACHE CLASSES

class SystemFileCache {

    private static $id = '';
    private static $tempDirectory;

    public static function getTempDirectory(){
        if (self::$tempDirectory)
            return self::$tempDirectory;

        if (!file_exists(SYSTEM_CACHE_TMP_DIR)){
            if (!mkdir(SYSTEM_CACHE_TMP_DIR, 0777, true)){
                echo 'Cannot create a temp directory `' . SYSTEM_CACHE_TMP_DIR . '`';
                exit(1);
            }
        }
        return self::$tempDirectory = SYSTEM_CACHE_TMP_DIR;
    }

    public static function get($name){
        $file = SystemFileCache::getTempDirectory() . self::$id . 's.' . $name . '.php';
        if (file_exists($file) && filemtime($file) > time()){
            $result = include $file;
            return $result ? $result : null;
        }
        return null;
    }

    public static function getWithCheckFile($name, $path){
        $data = self::get($name);
        if ($data !== null){
            if (filemtime($path) > $data[0]){
                return null;
            }
        }
        return $data[1];
    }

    public static function set($name, $value, $expires = 3600){
        $file = self::getTempDirectory() . self::$id . 's.' . $name . '.php';
        file_put_contents($file, '<?php return ' . var_export($value, true) . ';');
        touch($file, time() + $expires);
    }

    public static function setWithCheckFile($name, $value, $path, $expires = 3600){
        $value = array(filemtime($path), $value);
        self::set($name, $value, $expires);
    }

    public static function setId($id){
        self::$id = $id;
    }
}

class SystemCache {

    const type = __CLASS__;

    private static $id = '';

    public static function isCached(){
        return SYSTEM_IN_MEM_CACHED === true;
    }

    public static function setId($id){
        self::$id = $id;
        SystemFileCache::setId($id);
    }

    protected static function getFromFile($name){
        $file = SystemFileCache::getTempDirectory() . sha1(self::$id . '.' . $name) . '.php';
        if (file_exists($file)){
            $result = include $file;
            return $result ? $result : null;
        }
        return null;
    }

    protected static function setToFile($name, $value){
        $file = SystemFileCache::getTempDirectory() . sha1(self::$id . '.' . $name) . '.php';
        file_put_contents($file, '<?php return ' . var_export($value, true) . ';');
    }

    public static function get($name, $cacheInFiles = false){
        return SYSTEM_IN_MEM_CACHED === true
            ? (($value = apc_fetch('$.s.' . self::$id . '.' . $name)) === false ? null : $value)
            : ($cacheInFiles ? self::getFromFile($name) : null);
    }

    public static function set($name, $value, $lifetime = 3600, $cacheInFiles = false){
        if ( SYSTEM_IN_MEM_CACHED === true ){
            if (!apc_store('$.s.' . self::$id . '.' . $name, $value, $lifetime)){
                echo 'Cannot put data to cache, may be out of memory, key: '.$name.' (fix APC or XCache)';
                exit(1);
            }
        } elseif ($cacheInFiles){
            self::setToFile($name, $value);
        }
    }

    public static function remove($name){
        if (SYSTEM_IN_MEM_CACHED === true)
            apc_delete($name);

        $file = SYSTEM_CACHE_TMP_DIR . sha1(self::$id . '.' . $name);
        if (is_file($file))
            @unlink($file);
    }

    public static function removeAll(){
        if (function_exists('apc_clear_cache'))
            apc_clear_cache('user');

        if (IS_CLI && SYSTEM_IN_MEM_CACHED)
            apc_store('$$$removeAll', true);

        $dir = new File(SYSTEM_CACHE_TMP_DIR);
        if ($dir->isDirectory())
            $dir->delete();
    }

    /**
     * @return bool
     */
    public static function isNeededForRemoveAll(){
        return !! apc_fetch('$$$removeAll');
    }

    public static function getWithCheckFile($name, $filePath, $cacheInFiles = false){
        if ( !SYSTEM_IN_MEM_CACHED && !$cacheInFiles )
            return null;

        $result = self::get($name, $cacheInFiles);

        if ( $result ){
            $upd    = (int)self::get($name . '.$upd', $cacheInFiles);
            if ($upd === 0)
                return null;

            $file = new File($filePath);
            if (!$file->isModified($upd, false)){
                return $result;
            }
        }
        return null;
    }

    public static function getIf($name, $callback, $cacheInFiles = false){
        if ( !SYSTEM_IN_MEM_CACHED && !$cacheInFiles )
            return null;

        if (REGENIX_IS_DEV === true && !is_callable($callback))
            throw new \InvalidArgumentException('Callback must be callable');

        $result = self::get($name, $cacheInFiles);
        if ($result){
            $st = call_user_func($callback);
            if ($st){
                return $result;
            }
        }
        return null;
    }

    public static function setWithCheckFile($name, $value, $filePath, $lifetime = 3600, $cacheInFiles = false){
        if ( !SYSTEM_IN_MEM_CACHED && !$cacheInFiles )
            return;

        self::set($name, $value, $lifetime, $cacheInFiles);
        if (file_exists($filePath)){
            $file = new File($filePath);
            self::set($name.'.$upd', $file->lastModified(), $lifetime, $cacheInFiles);
        }
    }

    public static function getFileContents($filePath, $lifetime = 3600){
        if ( SYSTEM_IN_MEM_CACHED ){
            $sha1  = '$.s.file.' . sha1($filePath);
            $inmem = apc_fetch($sha1 . '.$upd');
            if ( $inmem ){
                $mtime = file_exists($filePath) ? filemtime($filePath) : -1;
                if ( $inmem == $mtime ){
                    $result = apc_fetch($sha1, $success);
                    if ( $success )
                        return $result;
                }
            } else {

                $result = file_get_contents($filePath);
                if (file_exists($filePath)){
                    apc_store($sha1, $result, $lifetime);
                    apc_store($sha1 . '.$upd', filemtime($filePath), $lifetime);
                }
                return $result;
            }
        } else
            return file_get_contents($filePath);
    }
}

}

namespace {

    function _trait_exists($traitname, $autoload = true){
        if (function_exists('trait_exists'))
            return trait_exists($traitname, $autoload);
        else
            return false;
    }

    /**
     * Resolve class name, also analyzer check class names in this function
     * @param $className
     * @return mixed|string
     */
    function cl($className){
        $className = str_replace('.', '\\', $className);
        if ($className[0] === '\\')
            $className = substr($className, 1);

        return $className;
    }

    if(!function_exists('apc_store')){
        function apc_store($key, $var, $ttl = 0){
            if (is_object($var))
                $var = chr(1) . '$$$$' . serialize($var);

            return xcache_set($key, $var, $ttl);
        }

        function apc_fetch($key, &$success=true){
            $success = xcache_isset($key);
            if ($success){
                $value = (xcache_get($key));
                if (is_string($value) && strpos($value, chr(1) . '$$$$') === 0){
                    $value = unserialize(substr($value, 5));
                }
                return $value;
            } else
                return false;
        }

        function apc_delete($key){
            return xcache_unset($key);
        }

        function apc_exists($keys){
            if(is_array($keys)){
                $exists = array();
                foreach($keys as $key){
                    if(xcache_isset($key))
                        $exists[]=$key;
                }
                return $exists;
            }

            return xcache_isset($keys);
        }

        function apc_clear_cache($group = ''){
            if (!IS_CLI)
                xcache_clear_cache(XC_TYPE_PHP | XC_TYPE_VAR);
        }
    }
}