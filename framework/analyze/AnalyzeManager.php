<?php
namespace regenix\analyze;

use regenix\lang\ClassScanner;
use regenix\lang\File;
use regenix\lang\SystemCache;
use regenix\lang\SystemFileCache;

class AnalyzeManager {

    /** @var array */
    protected $meta;

    /** @var File */
    private $directory;

    /** @var array */
    protected $extensions;

    public function __construct($pathToDir, array $extensions = array('php')){
        $this->meta = SystemFileCache::get(sha1($pathToDir) . '.analyze');
        if ($this->meta == null){
            $this->meta = array();
        }
        $this->directory = new File($pathToDir);
        $this->extensions = $extensions;
    }

    protected function saveMeta(){
        if (!$this->meta['$$$upd'])
            $this->meta['$$$upd'] = $this->directory->lastModified();

        SystemFileCache::set(sha1($this->directory->getPath()) . '.analyze', $this->meta);
    }

    protected function analyzeFile(File $file){
        $info = ClassScanner::find(Analyzer::type);
        $childrens = $info->getChildrensAll();
        foreach($childrens as $children){
            $analyzer = $children->newInstance(array($this, $file));
            $analyzer->analyze();
        }
    }

    public function analyze($incremental = true){
        $upd = $this->meta['$$$upd'];
        if (!$upd || $this->directory->isModified($upd)){
            $files = $this->directory->findFiles(true);
            foreach($files as $file){
                $meta =& $this->meta[$file->getPath()];

                if ($meta['upd'] && $file->lastModified() <= $meta['upd'])
                    continue;

                if ($file->hasExtensions($this->extensions)){
                    $this->analyzeFile($file);
                    $meta['upd'] = $file->lastModified();
                    if ($incremental)
                        $this->saveMeta();
                }
            }
        }

        $this->meta['$$$upd'] = $this->directory->lastModified();
        $this->saveMeta();
    }
}

class AnalyzeFileInformation {
    protected $modified;
    protected $name;
    protected $length;

    public function __construct(File $file){
        $this->modified = $file->lastModified();
        $this->name = $file->getName();
        $this->length = $file->length();
    }

    public function getLength(){
        return $this->length;
    }

    public function getModified(){
        return $this->modified;
    }

    public function getName(){
        return $this->name;
    }
}