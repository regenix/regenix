<?php
namespace framework\lang;

abstract class String {

    const type = __CLASS__;

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
