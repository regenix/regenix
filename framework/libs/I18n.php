<?php
namespace framework\libs;


use framework\Project;
use framework\cache\SystemCache;
use framework\config\PropertiesConfiguration;
use framework\io\File;
use framework\lang\IClassInitialization;
use framework\mvc\Request;
use framework\mvc\RequestQuery;
use framework\mvc\Session;
use framework\mvc\route\Router;

class I18n implements IClassInitialization {

    const type = __CLASS__;

    private static $messages = array();
    private static $lang = 'default';

    private static $detectType = 'none';
    private static $detectArg  = '_lang';

    public static function setMessages($lang, $messages){
        self::$messages[$lang] = $messages;
    }

    public static function setLang($lang, $save = true){
        self::$lang = str_replace(array(' ', '-'), '_', strtolower($lang));

        if ( $save ){
            switch(self::$detectType){
                case 'session': {
                    Session::current()->put( self::$detectArg, self::$lang );
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
            $lang = self::detectLang();
            return !$lang || self::availLang($lang);
        } else
            return self::$loader && (self::$messages[$lang] || self::$loader->loadLang($lang));
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
     * @return bool|\framework\mvc\scalar|null|string
     */
    public static function detectLang(){
        $project = Project::current();
        $request = Request::current();

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
                $lang = $project->router->args[self::$detectArg];
            } break;
            case 'get': {
                $lang = RequestQuery::current()->getString(self::$detectArg);
            } break;
            case 'session': {
                $lang = Session::current()->get(self::$detectArg);
            } break;
            default: {
                $lang = $project->config->getString('i18n.lang', 'default');
            }
        }
        return $lang;
    }

    public static function initialize() {
        self::setLoader(new I18nDefaultLoader());

        $project = Project::current();
        $request = Request::current();

        if ( $request ){
            $type = $project->config->getString('i18n.detect.type', 'none');
            $arg  = $project->config->getString('i18n.detect.arg', '_lang');

            self::$detectType = $type;
            self::$detectArg  = $arg;

            $lang = self::detectLang();

            if ($lang && self::availLang($lang))
                self::setLang($lang);
        }
    }
}


abstract class I18nLoader {

    abstract public function loadLang($lang);
}

class I18nDefaultLoader extends I18nLoader {

    public function loadLang($lang) {
        $project = Project::current();
        $file = $project->getPath() . 'conf/i18n/' . $lang . '.lang';
        if ( file_exists($file) ){
            $messages = SystemCache::getWithCheckFile('i18n.' . $lang, $file);
            if ( $messages === null ){
                $config   = new PropertiesConfiguration(new File($file));
                $messages = $config->all();
                SystemCache::setWithCheckFile('i18n.' . $lang, $messages, $file);
            }
            I18n::setMessages($lang, $messages);
            return true;
        }
        return false;
    }
}