<?php
namespace regenix\analyze;

use regenix\config\Configuration;
use regenix\config\PropertiesConfiguration;
use regenix\lang\ClassScanner;
use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\lang\String;
use regenix\lang\SystemCache;
use regenix\lang\SystemFileCache;

class AnalyzeManager {

    /** @var array */
    protected $meta;

    /** @var File */
    private $directory;

    /** @var string[] */
    private $ignorePaths = array();

    /** @var array */
    protected $extensions;

    /** @var PropertiesConfiguration */
    protected $configuration;

    public function __construct($pathToDir, array $extensions = array('php')){
        $this->meta = SystemFileCache::get(sha1($pathToDir) . '.analyze');
        if ($this->meta == null){
            $this->meta = array();
        }
        $this->directory = new File($pathToDir);
        $this->extensions = $extensions;
        $this->configuration = new PropertiesConfiguration();
    }

    /**
     * Add ignore path for ignore
     * @param $path
     */
    public function addIgnorePath($path){
        $path = str_replace('\\', '/', $path);
        if (substr($path, -1) !== '/')
            $path .= '/';

        if ($path[0] !== '/')
            $path = $this->directory->getPath() . $path;

        $this->ignorePaths[$path] = $path;
    }

    private function isIgnorePath($path){
        $path = str_replace('\\', '/', $path);
        if (substr($path, -1) !== '/')
            $path .= '/';

        foreach($this->ignorePaths as $one){
            if (String::startsWith($path, $one))
                return true;
        }
        return false;
    }

    /**
     * @return \regenix\lang\File
     */
    public function getDirectory(){
        return $this->directory;
    }


    /**
     * @return \regenix\config\PropertiesConfiguration
     */
    public function getConfiguration(){
        return $this->configuration;
    }

    public function setConfiguration(PropertiesConfiguration $configuration){
        $this->configuration = $configuration;
    }

    protected function saveMeta(){
        if (!$this->meta['$$$upd'])
            $this->meta['$$$upd'] = $this->directory->lastModified();

        SystemFileCache::set(sha1($this->directory->getPath()) . '.analyze', $this->meta);
    }

    public function analyzeFile(File $file){
        $parser = new \PHPParser_Parser(new \PHPParser_Lexer_Emulative());
        try {
            $content = $file->getContents();
            $statements = $parser->parse($content);
        } catch (\PHPParser_Error $e) {
            throw new ParseException($file, $e->getRawLine(), $e->getMessage());
        }

        $info = ClassScanner::find(Analyzer::type);
        $childrens = $info->getChildrensAll();
        /** @var $analyzers Analyzer[] */
        $analyzers = array();
        foreach($childrens as $children){
            $analyzers[] = $children->newInstance(array($this, $file, $statements, $content));
        }

        usort($analyzers, function($a, $b){
            /** @var $a Analyzer */
            /** @var $b Analyzer */
            if ($a->getSort() === $b->getSort())
                return 0;
            return $a->getSort() > $b->getSort() ? 1 : -1;
        });

        foreach($analyzers as $analyzer){
            $analyzer->analyze();
        }
    }

    public function analyze($incremental = true, $ignoreCache = false,
                            $callbackException = null, $callbackScan = null){
        $upd = $this->meta['$$$upd'];
        $fail = false;
        if ($ignoreCache || (!$upd || $this->directory->isModified($upd))){
            $files = $this->directory->findFiles(true);
            foreach($files as $file){
                $path = $file->getParent();
                if ($this->isIgnorePath($path)){
                    continue;
                }

                if ($file->hasExtensions($this->extensions)){
                    try {
                        if ($callbackScan)
                            call_user_func($callbackScan, $file);

                        $this->analyzeFile($file);
                    } catch (AnalyzeException $e){
                        $fail = true;
                        if (!$incremental){
                            if (!$callbackException)
                                throw new CoreException('Please pass a callback for non-incremental mode');

                            call_user_func($callbackException, $e);
                        } else {
                            if ($callbackException)
                                call_user_func($callbackException, $e);
                            else
                                throw $e;
                        }
                    }
                }
            }
        }

        if (!$fail){
            $this->meta['$$$upd'] = $this->directory->lastModified();
            $this->saveMeta();
        }
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