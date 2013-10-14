<?php
namespace regenix\frontend;

use regenix\lang\File;

abstract class Language {

    const type = __CLASS__;

    /** @var FrontendManager */
    protected $manager;

    /**
     * @param FrontendManager $manager
     */
    public function __construct(FrontendManager $manager){
        $this->manager = $manager;
    }

    abstract public function getName();
    abstract public function getExtension();
    abstract public function getHtmlInsert($path, array $arguments = array());

    /**
     * @return bool
     */
    public function isMonolithic(){
        return false;
    }


    /**
     * @param File $file
     * @return File
     */
    public function getOutFile(File $file){
        return $file;
    }

    /**
     * @param File $file
     * @param File $outFile
     */
    public function processing(File $file, File $outFile){}
}