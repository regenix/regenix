<?php
namespace framework\utils;

use framework\cache\SystemCache;
use framework\exceptions\CoreException;

class Annotations {

    const type = __CLASS__;

    private static $ignories = array(
        'var', 'param', 'return', 'internal', 'throws', 'inheritdoc', 'deprecated', 'link', 'see'
    );

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

        if ( IS_DEV ){
            $this->validate();
        }
    }

    /**
     * Validate structure of annotation
     */
    public function validate(){

        if ( $this->isEmpty() )
            return;

        foreach($this->data as $name => $item){
            $this->validateItem($name, $item);
        }
    }


    private function validateItem($name, $item){

        if (in_array($name, self::$ignories, true))
            return;

        $meta = self::$types[ $this->scope ][ $name ];
        if ( !$meta )
            throw CoreException::formated('@%s annotation is not defined', $name);

        // check multi
        if ( isset($item[0]) && !$meta['multi'] )
            throw CoreException::formated('@%s annotation can\'t multiple', $name);

        if ( is_array($item) )
            foreach($item as $one) self::validateItemOne($one, $name, $meta);
        else
            self::validateItemOne($item, $name, $meta);
    }

    private function validateItemOne($item, $name, $meta){

        // check requires
        if ( $meta['require'] ){
            foreach($meta['require'] as $req){
                if ( !isset($item[$req]) )
                    throw CoreException::formated('[@%s annotation]: `%s` field is required', $name, $req);
            }
        }

        // check types
        if ( $meta['fields'] ){
            foreach($meta['fields'] as $nm => $type){

                $value = $item[$nm];
                if ( $value === null ) continue;

                switch($type){
                    case 'double':
                    case 'float':
                    case 'numeric':
                    case 'number': {

                        if ( !filter_var($value, FILTER_VALIDATE_INT) || !filter_var($value, FILTER_VALIDATE_FLOAT))
                            throw CoreException::formated('[%s annotation]: `%s` field must be INTEGER or DOUBLE type', $name, $nm);

                    } break;
                    case 'integer':
                    case 'long':
                    case 'int': {
                        if ( !is_numeric($value) )
                            throw CoreException::formated('[%s annotation]: `%s` field must be INTEGER type', $name, $nm);
                    } break;
                    case 'string': {
                        if (is_numeric($value[0]))
                            throw CoreException::formated('[%s annotation]: `%s` field must be STRING type', $name, $nm);

                    } break;
                    case 'bool':
                    case 'boolean': {
                        if (!($value === false || $value === true
                            || $value === '0' || $value === '1'
                            || $value === 'true' || $value === 'false')){

                            throw CoreException::formated('[%s annotation]: `%s` field must be BOOLEAN type', $name, $nm);
                        }
                    } break;
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

    private static $cached = array();

    private static function getAnnotation($comments, $cacheCode, $fileName, $line, $scope){

        if (IS_PROD !== true){

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
     * @return \framework\utils\Annotations
     */
    public static function getClassAnnotation($class){

        if ( !($class instanceof \ReflectionClass) )
            $class = new \ReflectionClass($class);

        return self::getAnnotation($class->getDocComment(), $class->getName(),
                    IS_DEV ? $class->getFileName() : null,
                    IS_DEV ? $class->getStartLine() : -1,
                    'class',
                    IS_DEV ? $class->getName() : null
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
                    IS_DEV ? $class->getFileName() : null,
                    IS_DEV ? $class->getStartLine() : -1,
                    'property',
                    IS_DEV ? $property->getName() : null
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
            IS_DEV ? $class->getFileName() : null,
            IS_DEV ? $class->getStartLine() : -1,
            'method',
            IS_DEV ? $method->getName() : null
        );
    }



    /**
     * parse psevdo annotation
     * @param $line
     * @internal param string $comment
     * @return array
     */
    private static function parseAnnotation($line){

        $tmp = explode('@', $line);
        if ( sizeof($tmp) < 2 ) return null;

        $result = array();

        foreach ($tmp as $i => $line) {

            $values = explode(' ', $line, 2);
            if ( $values[0] ){
                $value = trim($values[1]);
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
                }

                $result[strtolower($values[0])] = $el;
            }
        }

        return count($result) ? $result : null;
    }

    private static function parseAnnotations($comment){

        if ( !$comment ) return null;
        if (strpos( $comment, '/**') !== 0)
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
     * @param $canArgs array
     * @param $name string
     * @param $scopes array|string
     */
    public static function registerAnnotation($type, array $info = array(),
                                              $scopes = array('class', 'method', 'property')){
        if ( is_string($scopes) )
            $scopes = array($scopes);

        foreach($scopes as $scope){
            if ( self::$types[$scope][$type] )
                throw CoreException::formated('Annotation @%s already registered in `%s` scope', $type, $scope);

            self::$types[ $scope ][ $type ] = $info;
        }
    }
}