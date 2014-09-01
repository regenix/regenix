<?php
namespace regenix\i18n;

use regenix\core\Application;
use regenix\core\Regenix;
use regenix\lang\DI;
use regenix\lang\IClassInitialization;
use regenix\mvc\http\Cookie;
use regenix\mvc\http\Request;
use regenix\mvc\http\RequestQuery;
use regenix\mvc\http\session\Session;
use regenix\mvc\route\Router;

class I18n implements IClassInitialization {

    const type = __CLASS__;

    private static $messages = array();
    private static $lang = 'default';

    private static $detectType = 'none';
    private static $detectArg  = '_lang';

    public static function setMessages($lang, $messages){
        self::$messages[$lang] = $messages;
    }

    public static function getMessages($lang){
        if (!self::$messages[$lang]){
            self::$loader->loadLang($lang);
        }
        return self::$messages[$lang];
    }

    public static function setLang($lang, $save = true){
        self::$lang = str_replace(array(' ', '-'), '_', strtolower($lang));

        if ( $save ){
            switch(self::$detectType){
                case 'session': {
                    $session = DI::getInstance(Session::type);
                    $session->put( self::$detectArg, self::$lang );
                } break;
                case 'cookie': {
                    $cookie = Cookie::getInstance();
                    $cookie->put( self::$detectArg, self::$lang, 1000 * 1000 * 1000 );
                } break;
            }
        }
    }

    public static function getLang(){
        return self::$lang;
    }

    /**
     * @param $lang
     * @return mixed
     */
    public static function availLang($lang = null){
        if ( $lang === null ){
            $lang = self::detectLang(DI::getInstance(Request::type));
            return !$lang || self::availLang($lang);
        } else
            return self::$loader && (self::$messages[$lang] || self::$loader->loadLang($lang));
    }


    /**
     * return unique hash of lang from last update
     * @param string $lang
     * @return string
     */
    public static function getLangStamp($lang){
        return md5(self::$loader->getLastUpdate($lang));
    }

    /**
     * i18n format string
     * @param string $message
     * @param array $args
     * @return string
     */
    public static function format($message, array $args){
        $keys = array_map(function($key){
            return '{' . $key . '}';
        }, array_keys($args));

        return str_replace($keys, $args, $message);
    }

    /**
     * @param $message
     * @param string|array $args
     * @return string
     */
    public static function get($message, $args = ''){
        $lang = self::$lang;

        if ( !self::$messages[$lang] )
            self::$loader->loadLang($lang);

        if ( $tmp = self::$messages[ $lang ][$message] )
            $message = $tmp;

        if (is_array($args))
            return self::format($message, $args);
        else
            return self::format($message, array_slice(func_get_args(), 1));
    }

    /** @var I18nLoader */
    private static $loader;
    public static function setLoader(I18nLoader $loader){
        self::$loader = $loader;
    }

    /**
     * @param \regenix\mvc\http\Request $request
     * @return bool|scalar|null|string
     */
    public static function detectLang(Request $request){
        $app = Regenix::app();

        $lang = false;
        switch(self::$detectType){
            case 'headers': {
                $languages = $request->getLanguages();
                foreach($languages as $lang){
                    if ( self::availLang($lang) ){
                        return $lang;
                    }
                }
            } break;
            case 'route': {
                $lang = $app->router->args[self::$detectArg];
            } break;
            case 'get': {
                $query = DI::getInstance(RequestQuery::type);
                $lang = $query->getString(self::$detectArg);
            } break;
            case 'session': {
                $session = DI::getInstance(Session::type);
                $lang = $session->get(self::$detectArg);
            } break;
            case 'cookie': {
                $cookie = Cookie::getInstance();
                $lang = $cookie->get(self::$detectArg, 'default');
            } break;
            default: {
                $lang = $app->config->getString('i18n.lang', 'default');
            }
        }

        if (!$lang)
            $lang = 'default';
        
        return $lang;
    }

    public static function initialize() {
        self::setLoader(new I18nDefaultLoader());

        $app = Regenix::app();
        $request = DI::getInstance(Request::type);

        if ( $request ){
            $type = $app->config->getString('i18n.detect.type', 'none');
            $arg  = $app->config->getString('i18n.detect.arg', '_lang');

            self::$detectType = $type;
            self::$detectArg  = $arg;

            $lang = self::detectLang($request);

            if ($lang && self::availLang($lang))
                self::setLang($lang);
        }
    }
}
