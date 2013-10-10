<?php
namespace regenix\libs\ws;

use regenix\lang\CoreException;
use regenix\lang\String;

final class WS {

    private function __construct(){}

    /**
     * @param $url
     * @return WSRequest
     */
    public static function url($url){
        return new WSRequest($url);
    }


    /**
     * Build HTTP Query
     *
     * @param array $params Name => value array of parameters
     * @return string HTTP query
     **/
    public static function buildHttpQuery(array $params){
        if (empty($params)) {
            return '';
        }

        $keys = self::urlencode(array_keys($params));
        $values = self::urlencode(array_values($params));
        $params = array_combine($keys, $values);

        uksort($params, 'strcmp');

        $pairs = array();
        foreach ($params as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }

        return implode('&', $pairs);
    }


    /**
     * URL Encode
     * @param string|array $item
     * @return mixed - array or string
     */
     public static function urlencode($item){

        static $search  = array('+', '%7E');
        static $replace = array('%20', '~');

        if (is_array($item))
            return array_map(array(__CLASS__, __FUNCTION__), $item);

        if (is_scalar($item) === false)
            return $item;

        return str_replace($search, $replace, rawurlencode($item));
    }

    /**
     * URL Decode
     * @param string|array $item
     * @return string|array Url decode string
     */
     public static function urldecode($item){
        if (is_array($item)) {
            return array_map(array(__CLASS__, __FUNCTION__), $item);
        }

        return rawurldecode($item);
    }
}
