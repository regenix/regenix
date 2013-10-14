<?php
namespace regenix\template\tags;

use regenix\core\Regenix;
use regenix\exceptions\FileNotFoundException;
use regenix\frontend\FrontendManager;
use regenix\lang\DI;
use regenix\lang\File;
use regenix\lang\String;
use regenix\mvc\http\UploadFile;
use regenix\template\RegenixTemplate;
use regenix\template\RegenixTemplateTag;
use regenix\mvc\template\TemplateLoader;

class RegenixAssetTag implements RegenixTemplateTag {

    /** @var FrontendManager */
    private $manager;

    public function __construct(FrontendManager $manager){
        $this->manager = $manager;
    }

    public function getName(){
        return 'asset';
    }

    private function callDep($group, array $args){
        $version = $args['version'];
        $info = Regenix::app()->getAsset($group, $version);

        return $path   = '/assets/' . $group . '~' . $info['version'] . '/';
    }

    public function call($args, RegenixTemplate $ctx){
        if (String::startsWith($args['_arg'], 'dep:'))
            return $this->callDep(substr($args['_arg'], 4), $args);

        return $this->get($args['_arg']);
    }

    public function get($name){
        if (String::startsWith($name, 'http://')
            || String::startsWith($name, 'https://') || String::startsWith($name, '//'))
            return $name;

        $path = TemplateLoader::$ASSET_PATH . $name;
        $path = str_replace(array('\\', '////', '///', '//'), '/', $path);

        $file = new File(str_replace(array('\\', '////', '///', '//'), '/', ROOT . $path));

        if (IS_DEV && $name && !$file->exists()){
            throw new FileNotFoundException($file);
        }

        $outFile = $this->manager->processing($file);
        if ($outFile === null)
            return $path;
        else {
            return UploadFile::convertPathToUrl($outFile->getPath());
        }
    }
}
