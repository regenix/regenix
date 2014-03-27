<?php
namespace regenix\mvc;

use regenix\lang\SystemCache;
use regenix\exceptions\AnnotationException;
use regenix\lang\CoreException;
use regenix\lang\ArrayTyped;
use regenix\lang\String;

class Annotations {

    const type = __CLASS__;

    /** @var string */
    private $file;

    /** @var integer */
    private $line = -1;

    /** @var string */
    private $linkName;

    /** @var string */
    private $scope = '';

    /** @var array|null */
    private $data = null;

    /**
     * @param string $comment
     */
    public function __construct($comment, $file, $line, $scope = '', $linkName = ''){
        $this->data = self::parseAnnotations($comment);
        $this->scope = $scope;
        $this->line  = $line;
        $this->file  = $file;
        $this->linkName = $linkName;
    }

    private function checkAnnotation($annotation){
        if ( self::$types[ $this->scope ][strtolower($annotation)] === null )
            throw new AnnotationException($this, $annotation, ' is not defined');
    }

    /**
     * @param string $annotation
     * @return ArrayTyped
     */
    public function get($annotation){
        if ( REGENIX_IS_DEV ) $this->checkAnnotation($annotation);

        $value = $this->data[strtolower($annotation)];
        if ( $value[0] !== null )
            throw new AnnotationException($this, $annotation, ' can\'t get array typed, use ->getAsArray() method');

        return new ArrayTyped($value);
    }


    /**
     * return true if annotation exists
     * @param string $annotation
     * @return bool
     */
    public function has($annotation){
        if ( REGENIX_IS_DEV ) $this->checkAnnotation($annotation);

        return isset($this->data[strtolower($annotation)]);
    }

    /**
     * @param string $annotation
     * @return ArrayTyped[]
     */
    public function getAsArray($annotation){
        if ( REGENIX_IS_DEV ) $this->checkAnnotation($annotation);

        $result = array();
        $value = $this->data[$annotation];
        if ( $value ){
            if (isset($value[0])){

                foreach($value as $el){
                    $result[] = new ArrayTyped($el);
                }

            } else {
                $result[] = new ArrayTyped($value);
            }
        }

        return $result;
    }

    private static $validated = array();

    /**
     * Validate structure of annotation
     */
    public function validate(){
        if ( $this->isEmpty() )
            return;

        $hash = $this->file . '#' . $this->line . '#' . $this->linkName . '#' . $this->scope;
        if ( self::$validated[ $hash ] )
            return;

        foreach($this->data as $name => $item){
            $this->validateItem($name, $item);
        }

        self::$validated[ $hash ] = true;
    }


    private function validateItem($name, $item){
        $meta = self::$types[ $this->scope ][ $name ];

        if ( $meta === null )
            throw new CoreException('@%s annotation is not defined', $name);

        // check multi
        if ( isset($item[0]) && !$meta['multi'] )
            throw new CoreException('@%s annotation can\'t multiple', $name);

        if ( is_array($item) )
            foreach($item as $one){
                self::validateItemOne($one, $name, $meta);
            }
        else
            self::validateItemOne($item, $name, $meta);
    }

    private function validateItemOne($item, $name, $meta){
        if ( !is_array($item) ){
            $item = array('_arg' => $item);
        }

        // check requires
        if ( $meta['require'] ){
            foreach($meta['require'] as $req){

                if ( !isset($item[$req]) ){
                    throw new CoreException('[@%s annotation]: `%s` field is required', $name, $req);
                }
            }
        }

        // check names
        if ( !$meta['any'] ){
            foreach($item as $nm => $value){
                if ( !$meta['fields'][$nm] ){
                    throw new AnnotationException($this, $name, String::format('field `%s` not defined', $nm));
                }
            }
        }

        // check types
        if ( $meta['fields'] ){
            foreach((array)$meta['fields'] as $nm => $type){

                $value = $item[$nm];
                if ( $value === null ){
                    continue;
                }

                switch($type){
                    case 'double':
                    case 'float':
                    case 'numeric':
                    case 'number': {

                        if ( !filter_var($value, FILTER_VALIDATE_INT) || !filter_var($value, FILTER_VALIDATE_FLOAT))
                            throw new CoreException('[%s annotation]: `%s` field must be INTEGER or DOUBLE type', $name, $nm);

                    } break;
                    case 'integer':
                    case 'long':
                    case 'int': {
                        if ( !is_numeric($value) )
                            throw new CoreException('[%s annotation]: `%s` field must be INTEGER type', $name, $nm);
                    } break;
                    case 'string': {
                        if (is_numeric($value[0]))
                            throw new CoreException('[%s annotation]: `%s` field must be STRING type', $name, $nm);

                    } break;
                    case 'bool':
                    case 'boolean': {
                        if (!($value === false || $value === true
                            || $value === '0' || $value === '1'
                            || $value === 'true' || $value === 'false')){

                            throw new CoreException('[%s annotation]: `%s` field must be BOOLEAN type', $name, $nm);
                        }
                    } break;
                    default: {


                    }
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function isEmpty(){
        return !$this->data || !sizeof($this->data);
    }

    /**
     * get line number of source file
     * @return int
     */
    public function getLine(){
        return $this->line;
    }

    /**
     * get source file of annotations
     * @return string
     */
    public function getFile(){
        return $this->file;
    }

    /*** STATIC ****/
    private static $cached = array();

    private static function getAnnotation($comments, $cacheCode, $fileName, $line, $scope){
        if (REGENIX_IS_DEV === true){

            if ( self::$cached[ $cacheCode ] ){
                return self::$cached[ $cacheCode ];
            }

            /** @var $result Annotations */
            $result = SystemCache::getWithCheckFile('annot.' . $cacheCode, $fileName);

            if ( $result === null ){
                /** @var $class \ReflectionClass */

                $result = new Annotations($comments, $fileName, $line, $scope);
                SystemCache::setWithCheckFile('annot.' . $cacheCode, $result, $fileName);
            }

            $result->validate();
            self::$cached[ $cacheCode ] = $result;

            return $result;

        } else {

            // aggressive cache
            if ( self::$cached[ $cacheCode ] ){
                return self::$cached[ $cacheCode ];
            }

            $result = SystemCache::get('annot.' . $cacheCode);

            if ( $result === null ){
                /** @var $class \ReflectionClass */

                $result = new Annotations($comments, $fileName, $line, $scope);
                SystemCache::set('annot.' . $cacheCode, $result);
            }

            self::$cached[ $cacheCode ] = $result;

            return $result;
        }
    }

    /**
     * @param \ReflectionClass|string $class
     * @return Annotations
     */
    public static function getClassAnnotation($class){
        if ( !($class instanceof \ReflectionClass) )
            $class = new \ReflectionClass($class);

        return self::getAnnotation($class->getDocComment(), $class->getName(),
                    REGENIX_IS_DEV ? $class->getFileName() : null,
                    REGENIX_IS_DEV ? $class->getStartLine() : -1,
                    'class',
                    REGENIX_IS_DEV ? $class->getName() : null
        );
    }

    /**
     * @param $property \ReflectionProperty
     * @return Annotations|mixed|null
     */
    public static function getPropertyAnnotation(\ReflectionProperty $property){
        $class = $property->getDeclaringClass();
        return self::getAnnotation(
                    $property->getDocComment(),
                    $class->getName() .'$p.'. $property->getName(),
                    REGENIX_IS_DEV ? $class->getFileName() : null,
                    REGENIX_IS_DEV ? $class->getStartLine() : -1,
                    'property',
                    REGENIX_IS_DEV ? $property->getName() : null
        );
    }

    /**
     * @param \ReflectionMethod $method
     * @return Annotations
     */
    public static function getMethodAnnotation(\ReflectionMethod $method){
        $class = $method->getDeclaringClass();
        return self::getAnnotation(
            $method->getDocComment(),
            $class->getName() .'$m.'. $method->getName(),
            REGENIX_IS_DEV ? $class->getFileName() : null,
            REGENIX_IS_DEV ? $class->getStartLine() : -1,
            'method',
            REGENIX_IS_DEV ? $method->getName() : null
        );
    }



    /**
     * parse psevdo annotation
     * @param $line
     * @internal param string $comment
     * @return array
     */
    private static function parseAnnotation($strline){
        $tmp = explode('@', $strline);
        if ( sizeof($tmp) < 2 ) return null;

        $result = array();
        foreach ($tmp as $i => $line) {
            $values = explode(' ', trim($line), 2);

            if ( $values[0] ){
                $value = trim($values[1]);
                if (!$value){
                    $result[strtolower($values[0])] = array();
                    continue;
                }

                $el = array();
                if (strpos( $value, ',') !== false || strpos( $value, '=') !== false){
                    $value = array_map('trim', explode(',', $value, 30));
                    foreach ($value as $v) {
                        if (strpos($v, '=') !== false){
                            $v = array_map('trim', explode('=', $v));
                            $el[$v[0]] = $v[1];
                        } else
                            $el[$v] = false;
                    }
                } else {
                    $el[$value] = array('_arg' => $value ? $value : true);
                    $el['_arg'] = $value;
                }

                $result[strtolower($values[0])] = $el;
            }
        }

        return $result;
    }

    private static function parseAnnotations($comment){
        if ( !$comment ) return null;
        if (strpos( $comment, '/*') !== 0)
            return null;

        $comment = trim(substr($comment, 3, -2));
        $comment = explode(PHP_EOL, $comment);
        $result = array();
        foreach ($comment as $line){
            $line = trim($line);
            if ( $line[0] == '*' )
                $line = substr($line, 1);

            $annot = self::parseAnnotation($line);
            if ( $annot == null )
                continue;

            list($key) = array_keys($annot);
            $el = current($annot);

            if ( $result[$key] ){
                if (!is_array( $result[$key][0] )){
                    $old = $result[$key];
                    $result[$key] = array($old);
                }
                $result[$key][] = $el;
            } else
                $result[$key] = $el;
        }

        return $result;
    }


    private static $types = array();

    /**
     * @param $type
     * @param array $info
     * @param $scopes array|string
     * @internal param array $canArgs
     * @internal param string $name
     */
    public static function registerAnnotation($type, array $info = array(),
                                              $scopes = array('class', 'method', 'property')){
        $type = strtolower($type);
        if ( is_string($scopes) )
            $scopes = array($scopes);

        foreach($scopes as $scope){
            /*if ( self::$types[$scope][$type] )
                throw new CoreException('Annotation @%s already registered in `%s` scope', $type, $scope);*/

            self::$types[ $scope ][ $type ] = $info;
        }
    }

    public static function getEmpty() {
        return new Annotations("", "", 0);
    }
}

Annotations::registerAnnotation('var', array(
    'fields' => '_arg'
), 'property');

Annotations::registerAnnotation('package', array(
    'fields' => '_arg'
), 'class');

if ( extension_loaded('eaccelerator') && (bool)ini_get('eaccelerator.enable') )
    throw new CoreException('Can`t use annotations with eAccelerator, Rerflections::getDocComments() not supports :(');