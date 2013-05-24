<?php
namespace framework\lang;

use framework\cache\SystemCache;
use framework\SDK;
use framework\exceptions\CoreException;
use framework\lang\String;

require 'framework/cache/SystemCache.php';
require 'framework/lang/String.php';
require 'framework/exceptions/CoreException.php';

if (!defined('T_TRAIT'))
    define('T_TRAIT', 355);

final class ClassMetaInfo {

    private $name;
    private $info;

    public function __construct($className, $info){
        $this->info = $info;
        $this->name = $className;
    }

    public function getName(){
        return $this->name;
    }

    public function getFilename(){
        return $this->info[0];
    }

    public function isClass(){
        return !$this->info[1] || $this->info[1] === T_CLASS;
    }

    public function isInterface(){
        return $this->info[1] === T_INTERFACE;
    }

    public function isTrait(){
        return $this->info[1] === T_TRAIT;
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
     * @param $namespace
     * @return ClassMetaInfo[]
     */
    public function getChildrens($namespace = ''){
        if ($items = $this->info[255]){
            if ($namespace && !String::endsWith($namespace, '\\'))
                $namespace .= '\\';

            foreach($items as &$el){
                if (!$namespace || String::startsWith($el, $namespace)){
                    $el = ClassScanner::find($el);
                }
            }
            unset($el);
            return $items;
        } else
            return array();
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
            require $this->getFilename();

            $implements = class_implements($class);
            if ( $implements[IClassInitialization::IClassInitialization_type] ){
                $class::initialize();
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
 * @package framework\lang
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

        if ($meta = self::$metaInfo[$className])
            return new ClassMetaInfo($className, $meta);
        else
            return null;
    }

    /**
     * run scan procedure
     */
    public static function scan($cached = true){
        if (sizeof(self::$paths) === 1){
            $path = current(self::$paths);
            $hash = sha1($path);

            $meta = $cached ? SystemCache::getWithCheckFile('lang.sc.m.' . $hash, $path, true) : null;
            if ($meta === null){
                self::scanPath($path);
                if ($cached){
                    SystemCache::setWithCheckFile('lang.sc.m.' . $hash, self::$metaInfo, $path, 3600, true);
                }
            } else
                self::$metaInfo = $meta;

        } else
        foreach(self::$paths as $path){
            $hash    = sha1($path);
            if (self::$scanned[$hash])
                continue;

            $results = $cached ? SystemCache::getWithCheckFile('lang.sc.' . $hash, $path, true) : null;
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
            self::$scanned[$hash] = true;
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
        if (is_dir($path)){
            if (substr($path, -1) !== '/')
                $path .= '/';

            self::$paths[$path] = $path;
        }

        if ($scan)
            self::scan();
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

        // if exists parent...
        if ($extends || $implements){
            if (!$implements)
                $implements = array();

            if ($extends){
                $implements[] = $extends;
                //array_unshift($implements, $extends);
            }

            foreach($implements as $extend){
                if (!self::$metaInfo[$extend])
                    self::$metaInfo[$extend] = array();

                $parent =& self::$metaInfo[$extend];
                $parent[255][] = $className;
            }
        }

        return self::$metaInfo[$className] = $result;
    }

    /**
     * scan one path
     * @param string $path
     * @return array
     */
    protected static function scanPath($path){
        // fix path string
        $path = str_replace(array('\\', '//', '///', '////'), '/', $path);

        $result = array();
        foreach(self::$extensions as $extension){
            foreach(glob($path . '*.' . $extension) as $file){
                $result = $result + self::scanFile($file);
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
                self::$debugUseClasses[] = $class;
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

    public static function registerDefault($rootPath){
        self::unregisterAutoLoader();
        self::registerAutoLoader();

        self::setIncludePath($rootPath);
        self::addClassPath($rootPath . 'framework/');
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
     * @return array
     * @throws static
     */
    public static function getDebugUseClasses(){
        if (!self::$debug)
            throw CoreException::formated('Unable to get a list of classes is not used in debug mode');

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

            /*$this->token[0] = token_name($this->token[0]);
            var_dump($this->token);*/
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

class ClassNotFoundException extends CoreException {

    const type = __CLASS__;

    public function __construct($className){
        parent::__construct( String::format('Class "%s" not found', $className) );
    }
}


interface IClassInitialization {

    const IClassInitialization_type = __CLASS__;

    public static function initialize();
}