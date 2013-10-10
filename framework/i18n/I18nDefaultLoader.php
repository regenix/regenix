<?php
namespace regenix\i18n;

use regenix\core\Regenix;
use regenix\config\PropertiesConfiguration;
use regenix\lang\File;
use regenix\lang\SystemCache;

class I18nDefaultLoader implements I18nLoader {

    protected function getLangFile($lang){
        $app =  Regenix::app();
        return $app->getPath() . 'conf/i18n/' . $lang . '.lang';
    }

    public function loadLang($lang) {
        $file = self::getLangFile($lang);
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

    public function getLastUpdate($lang){
        $file = self::getLangFile($lang);
        if (file_exists($file))
            return filemtime($file);
        else
            return -1;
    }
}