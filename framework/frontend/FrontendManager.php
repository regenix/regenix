<?php
namespace regenix\frontend;

use regenix\core\Application;
use regenix\core\Regenix;
use regenix\lang\ClassScanner;
use regenix\lang\DI;
use regenix\lang\File;

class FrontendManager {

    const type = __CLASS__;

    /** @var Language[] */
    private $languages = array();

    /** @var Application */
    private $application;

    /** @var File */
    private $assetPath;

    public function __construct(Application $application){
        $this->application = $application;
        $this->assetPath   = new File($application->getAssetPath());
        $this->registerLanguages();
    }

    private function registerLanguages(){
        $meta = ClassScanner::find(Language::type);
        foreach($meta->getAllChildren() as $class){
            if (!$class->isAbstract()){
                /** @var $language Language */
                $language = $class->newInstance(array($this));
                $this->languages[$language->getExtension()] = $language;
            }
        }
    }

    /**
     * @return bool
     */
    public function isDev(){
        return $this->application->isDev();
    }

    /**
     * @return bool
     */
    public function isProd(){
        return $this->application->isProd();
    }

    /**
     * @return \regenix\lang\File
     */
    public function getAssetPath() {
        return $this->assetPath;
    }

    /**
     * @param File $file
     * @param File $out
     * @return bool
     */
    protected function isNeedProcessing(File $file, File $out){
        $language = $this->getLanguageByFile($file);
        if ($language->isMonolithic()){
            if ($this->isDev()){
                $lastMod = $this->assetPath->lastModified(true, function(File $file) use ($language) {
                    return $file->isDirectory() || $file->hasExtension($language->getExtension());
                });
                return $out->lastModified() < $file->lastModified()
                    || ($lastMod > $file->lastModified());
            } else {
                if (REGENIX_STAT_OFF)
                    return false;

                return !$out->exists();
            }
        } else {
            if (REGENIX_STAT_OFF)
                return false;

            return ($out->lastModified() < $file->lastModified());
        }
    }

    /**
     * @param $extension
     * @return Language
     */
    public function getLanguage($extension){
        return $this->languages[$extension];
    }

    /**
     * @param File $file
     * @return Language
     */
    public function getLanguageByFile(File $file){
        return $this->getLanguage($file->getExtension());
    }

    /**
     * @param File $file
     * @return File
     * @throws LanguageException
     */
    public function processing(File $file){
        $language = $this->getLanguageByFile($file);
        if ($language){
            $outFile = $language->getOutFile($file);

            if ($outFile !== $file && $this->isNeedProcessing($file, $outFile)){
                $language->processing($file, $outFile);
            }
            return $outFile;
        }
        return $file;
        /*throw new LanguageException('Language for "%s" file is not registered',
            $file->getRelativePath(new File(ROOT))
        );*/
    }
}