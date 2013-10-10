<?php
namespace regenix\template\tags;

use regenix\exceptions\FileNotFoundException;
use regenix\lang\File;
use regenix\lang\String;
use regenix\template\RegenixTemplate;
use regenix\template\RegenixTemplateTag;
use regenix\mvc\template\TemplateLoader;

class RegenixAssetTag implements RegenixTemplateTag {

    function getName(){
        return 'asset';
    }

    public function call($args, RegenixTemplate $ctx){
        return self::get($args['_arg']);
    }

    public static function get($name){
        if (String::startsWith($name, 'http://')
            || String::startsWith($name, 'https://') || String::startsWith($name, '//'))
            return $name;

        $path = TemplateLoader::$ASSET_PATH . $name;
        $path = str_replace('//', '/', $path);

        if ($name && !file_exists(ROOT . $path)){
            throw new FileNotFoundException(new File($path));
        }

        return $path;
    }
}
