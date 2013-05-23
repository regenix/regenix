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

    public function load(){
        class_exists($this->name);
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

    /** @var string */
    protected $includePath;

    /** @var array */
    protected $paths = array();

    /** @var array */
    protected $extensions = array();

    /** @var array */
    public $metaInfo = array();

    protected function __construct(){
        $this->addSourceExtension('php');
    }

    /**
     * scan with aggressive cache strategy
     */
    public function scanCached(){
        ClassLoader::$scanner = $this;
        $this->metaInfo = SystemCache::getWithCheckFile('lang.sc.all', $this->includePath, true);
        if ($this->metaInfo === null){
            $this->scan();
            SystemCache::setWithCheckFile('lang.sc.all', $this->metaInfo, $this->includePath, 3600, true);
        }
    }

    /**
     * @param string $className
     * @return ClassMetaInfo
     */
    protected function findClassMeta($className){
        if ($className[0] === '\\')
            $className = substr($className, 1);

        if ($meta = $this->metaInfo[$className])
            return new ClassMetaInfo($className, $meta);
        else
            return null;
    }

    /**
     * run scan procedure
     */
    public function scan(){
        $this->clear();
        foreach($this->paths as $path){
            $hash    = sha1($path);
            $results = SystemCache::getWithCheckFile('lang.sc.' . $hash, $path, true);
            if ($results === null){
                $results = $this->scanPath($path);
                SystemCache::setWithCheckFile('lang.sc.' . $hash, $results, $path, 3600, true);
            } else {
                foreach($results as $className => $meta){
                    $this->addClassMeta($className, $meta);
                }
            }
        }
        ClassLoader::$scanner = $this;
    }

    /**
     * Clear all meta information
     */
    public function clear(){
        unset($this->metaInfo);
        $this->metaInfo = array();
    }

    /**
     * @param string $path
     */
    public function setIncludePath($path){
        $this->includePath = $path;
    }

    /**
     * Add path with php files for scan
     * @param $path
     */
    public function addClassPath($path){
        if (is_dir($path)){
            if (substr($path, -1) !== '/')
                $path .= '/';

            $this->paths[] = $path;
        }
    }

    /**
     * @param string $extension
     */
    public function addSourceExtension($extension){
        $this->extensions[$extension] = $extension;
    }

    /**
     * @param string $extension
     */
    public function removeSourceExtension($extension){
        unset($this->extensions[$extension]);
    }

    protected function addClassMeta($className, $meta){
        $result = array();
        $result[0] = str_replace($this->includePath, '', $meta[0]);

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
                array_unshift($implements, $extends);
            }

            foreach($implements as $extend){
                if (!$this->metaInfo[$extend]){
                    $this->metaInfo[$extend] = array(255 => array());
                }

                $parent =& $this->metaInfo[$extend];
                $parent[255][] = $className;
            }
        }

        return $this->metaInfo[$className] = $result;
    }

    /**
     * scan one path
     * @param string $path
     * @return array
     */
    protected function scanPath($path){
        // fix path string
        $path = str_replace(array('\\', '//', '///', '////'), '/', $path);

        $result = array();
        foreach($this->extensions as $extension){
            foreach(glob($path . '*.' . $extension) as $file){
                $result = $result + $this->scanFile($file);
            }
        }

        foreach(scandir($path) as $file){
            if ($file == '.' || $file == '..') continue;
            if (is_dir($file = $path . $file)){
                $result = $result + $this->scanPath($file . '/');
            }
        }

        return $result;
    }

    protected function scanFile($file){
        $scanner = new ClassFileScanner($file);
        $result  = $scanner->getMeta();

        foreach($result as $className => $meta){
            $this->addClassMeta($className, $meta);
        }
        unset($scanner);

        return $result;
    }

    private static $instance;

    /**
     * @return ClassScanner
     */
    public static function current(){
        if ($result = static::$instance)
            return $result;

        return static::$instance = new static();
    }

    /**
     * @param string $className
     * @return ClassMetaInfo
     */
    public static function find($className){
        return self::current()->findClassMeta($className);
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


class ClassLoader {

    const type = __CLASS__;

    private $classPaths = array();
    private $classLibPaths = array();
    private $namespaces = array();

    /**
     * @var ClassLoader
     */
    public static $frameworkLoader;

    /** @var ClassScanner */
    public static $scanner;

    /**
     * @param $fileName string
     * @param $class string
     * @throws static
     */
    protected function checkCaseFilename($fileName, $class){
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);

        if ( !String::endsWith($fileName, $class . '.php') )
            throw CoreException::formated('Unable load `%s`, "%s.php" class file name case sensitive', $class,  $class);
    }

    public function addClassPath($path, $prepend = false) {
        if ($prepend)
            array_unshift( $this->classPaths, $path );
        else
            $this->classPaths[] = $path;
    }

    public function addClassLibPath($path, $prepend = false) {
        if ($prepend)
            array_unshift( $this->classLibPaths, $path );
        else
            $this->classLibPaths[] = $path;
    }

    public function addNamespace($namespace, $path, $prepend = false, $callback = null) {
        if ($prepend) {
            array_shift( $this->namespaces, array('namespace' => $namespace, 'path' => $path, 'callback' => $callback) );
        } else {
            $this->namespaces[] = array('namespace' => $namespace, 'path' => $path, 'callback' => $callback);
        }
    }

    public function loadClass($class) {
        if (self::$scanner){
            $meta = ClassScanner::find($class);
            if ($meta){
                $file = $meta->getFilename();
            }
        } else
            $file = $this->findFile( $class );

        if ($file != null){
            require $file;

            if (!class_exists( $class, false ) && !interface_exists( $class, false )
                    && !trait_exists($class, false))
                throw new ClassNotFoundException( $class );

            $implements = class_implements($class);
            if ( $implements[IClassInitialization::_type] ){
                $class::initialize();
            }
        }
    }

    public function findFile($class) {
        $class_rev = str_replace( '\\', DIRECTORY_SEPARATOR, $class );
        if (strpos( $class_rev, DIRECTORY_SEPARATOR ) === 0)
            $class_rev = substr( $class, 1 );

        foreach ($this->classPaths as $path) {
            $file = $path . $class_rev . '.php';
            if (file_exists( $file )) {
                return $file;
            }
        }

        foreach ($this->classLibPaths as $path){
            $tmp = explode(DIRECTORY_SEPARATOR, $class_rev, 2);
            $file = $path . $tmp[0] . '/lib/' . $class_rev . '.php';
            if (file_exists( $file ))
                return $file;
        }

        foreach ($this->namespaces as $item) {
            if ( !$item['namespace'] )
                $p = 1;
            else
                $p = strpos( $class, $item['namespace'] );

            if ($p !== false && $p < 2) {
                $file = $item['path'] . $class_rev . '.php';
                if (file_exists( $file )) {

                    if (IS_DEV)
                        $this->checkCaseFilename($file, $class);

                    if ( $item['callback'] )
                        call_user_func($item['callback']);

                    return $file;
                }
            }
        }

        return null;
    }

    public function register($prepend = false) {
        return spl_autoload_register( array($this, 'loadClass'), true, $prepend );
    }

    public function unregister() {
        return spl_autoload_unregister( array($this, 'loadClass') );
    }

    public static function load($class) {
        return class_exists( $class, true );
    }
}

/**
 * Faster optimize classloader for framework classes
 */
class FrameworkClassLoader extends ClassLoader {

    const type = __CLASS__;

    static $classes = array();

    public function loadClass($class) {
        // optimize
        $tmp = explode( '\\', $class, 3 );
        $check = $tmp[0] === 'framework';

        if ($check) {
            if ( IS_DEV ){
                $this->checkCaseFilename(
                    str_replace( '\\', DIRECTORY_SEPARATOR, $class ) . '.php',
                    $class
                );
            }

            /*if ($isModule && !SDK::isModuleRegister( $tmp[1] )) {
                throw CoreException::formated(
                        'Unable "%s" class load, module "%s" not registered',
                        $class, $tmp[1]);
            }*/
            $file = str_replace( '\\', DIRECTORY_SEPARATOR, $class ) . '.php';

            if (file_exists($file)){
                require $file;

                self::$classes[] = $class;
                $implements = class_implements($class);
                if ( $implements[IClassInitialization::_type] ){
                    $class::initialize();
                }

                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function findFile($class) {
        $file = str_replace( '\\', DIRECTORY_SEPARATOR, $class ) . '.php';
        return file_exists( $file ) ? $file : null;
    }

    public function register($prepend = false) {
        parent::register( $prepend );
        ClassLoader::$frameworkLoader = $this;
    }
}

class ClassNotFoundException extends CoreException {

    const type = __CLASS__;

    public function __construct($className){
        parent::__construct( String::format('Class "%s" not found', $className) );
    }
}


interface IClassInitialization {

    const _type = __CLASS__;

    public static function initialize();
}