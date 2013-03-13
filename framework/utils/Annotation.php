<?php
namespace framework\utils;

use framework\cache\SystemCache;

class Annotation {

    /** @var array|null */
    private $data = null;

    /**
     * @param string $comment
     */
    public function __construct($comment){
        $this->data = self::parseAnnotations($comment);
    }

    /**
     * @return bool
     */
    public function isEmpty(){
        return !$this->data || !sizeof($this->data);
    }

    private static $cached = array();

    /**
     * @param \ReflectionClass|string $class
     * @return \framework\utils\Annotation
     */
    public static function getClassAnnotation($class){

        if (IS_PROD !== true){
            if ( !($class instanceof \ReflectionClass) ){
                $class = new \ReflectionClass((string)$class);
            }

            $className = $class->getName();
            if ( self::$cached[ $className ] ){
                return self::$cached[ $className ];
            }
            $fileName = $class->getFileName();

            /** @var $result Annotation */
            $result = SystemCache::getWithCheckFile('annot.' . $className, $fileName);

            if ( $result === null ){
                /** @var $class \ReflectionClass */
                $comments = $class->getDocComment();
                $result = new Annotation($comments);
                SystemCache::setWithCheckFile('annot.' . $className, $result, $fileName);
            }
            self::$cached[ $className ] = $result;

            return $result;

        } else {

            // aggressive cache
            if ( is_string($class) ){

                if ( self::$cached[ $class ] ){
                    return self::$cached[ $class ];
                }

                $result = SystemCache::get('annot.' . $class);
                if ( $result !== null ){
                    self::$cached[ $class ] = $result;
                    return $result;
                } else {
                    $class = new \ReflectionClass($class);
                }
            }

            $className = $class->getName();
            if ( self::$cached[ $className ] )
                return self::$cached[ $className ];

            $result = SystemCache::get('annot.' . $class->getName());
            if ( $result === null ){

                /** @var $class \ReflectionClass */
                $comments = $class->getDocComment();
                $result = new Annotation($comments);
                SystemCache::set('annot.' . $class->getName(), $result);
            }
            self::$cached[ $className ] = $result;
        }
    }

    /**
     * parse psevdo annotation
     * @param $line
     * @internal param string $comment
     * @return array
     */
    private static function parseAnnotation($line){

        $tmp = explode('@', $line);
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
                    $el[$value] = false; // = array('_arg' => $value ? $value : true);
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
}