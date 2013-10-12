<?php
namespace regenix\analyze;

use regenix\analyze\exceptions\AnalyzeException;
use regenix\analyze\exceptions\ParseAnalyzeException;
use regenix\analyze\visitors\DefaultNodeVisitor;
use regenix\config\PropertiesConfiguration;
use regenix\lang\ClassScanner;
use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\lang\String;
use regenix\lang\SystemCache;
use regenix\lang\SystemFileCache;
use regenix\lang\types\Callback;

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

    /** @var array */
    private $uses = array();

    /** @var array */
    private $dependClasses;

    public function __construct($pathToDir, array $extensions = array('php')){
        $this->meta = SystemFileCache::get(sha1($pathToDir) . '.analyze');
        if ($this->meta == null){
            $this->meta = array();
        } else {
            $this->uses = $this->meta['$$$uses'];
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
        $this->meta['$$$uses'] = $this->uses;

        SystemFileCache::set(sha1($this->directory->getPath()) . '.analyze', $this->meta);
    }

    protected function addUses(File $file, array $uses){
        foreach($uses as $one){
            $this->uses[$one][$file->getPath()] = true;
        }
    }

    protected function addDependClasses($classes){
        foreach($classes as $class){
            $this->dependClasses[$class] = true;
        }
    }

    protected function getDependFiles(){
        $result = array();
        foreach($this->dependClasses as $class => $one){
            $files = $this->uses[$class];
            if ($files)
                foreach($files as $file => $el){
                    $result[$file] = new File($file);
                }
        }
        return $result;
    }

    public function analyzeFile(File $file){
        $parser = new \PHPParser_Parser(new \PHPParser_Lexer_Emulative());
        try {
            $content = $file->getContents();
            $statements = $parser->parse($content);
        } catch (\PHPParser_Error $e) {
            throw new ParseAnalyzeException($file, $e->getRawLine(), $e->getMessage());
        }

        $info = ClassScanner::find(Analyzer::type);
        $childrens = $info->getAllChildren();
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

        $uses = array();
        foreach($analyzers as $analyzer){
            $analyzer->analyze();
            $uses = array_merge($uses, $analyzer->getDependUses());
        }

        $traverser = new \PHPParser_NodeTraverser();
        $traverser->addVisitor(new \PHPParser_NodeVisitor_NameResolver());
        $traverser->addVisitor($visitor = new DefaultNodeVisitor($file, $analyzers));
        $traverser->traverse($statements);

        $uses = array();
        foreach($analyzers as $analyzer){
            $uses = array_merge($uses, $analyzer->getDependUses());
        }
        $uses = array_merge($uses, $visitor->getUses());

        $classes = $visitor->getClasses();
        $this->addUses($file, $uses);
        $this->addDependClasses($classes);
    }

    /**
     * @param File[] $files
     * @param bool $incremental
     * @param bool $ignoreCache
     * @param callable|\regenix\lang\types\Callback $callbackException
     * @param callable|\regenix\lang\types\Callback $callbackScan
     * @throws \regenix\lang\CoreException
     * @throws \Exception|exceptions\AnalyzeException
     * @return bool
     */
    protected function analyzeFiles(array $files, $incremental = true, $ignoreCache = false,
                                    Callback $callbackException = null, Callback $callbackScan = null){
        $fail = false;
        /** @var $file File */
        foreach($files as $file){
            $path = $file->getParent();
            if ($this->isIgnorePath($path)){
                continue;
            }

            if ($file->hasExtensions($this->extensions)){
                $meta =& $this->meta[$file->getPath()];
                if (!$meta){
                    $this->meta[$file->getPath()] = array();
                    $meta =& $this->meta[$file->getPath()];
                }

                if ($ignoreCache || !$meta['upd'] || $file->lastModified() > $meta['upd']){
                    try {
                        $callbackScan and $callbackScan->invoke($file);

                        $this->analyzeFile($file);
                        $meta['upd'] = $file->lastModified();

                    } catch (AnalyzeException $e){
                        $fail = true;
                        if (!$incremental){
                            if (!$callbackException || $callbackException->isNop())
                                throw new CoreException('Please pass a callback for non-incremental mode');

                            $callbackException and $callbackException->invoke($e);
                        } else {
                            if ($callbackException && !$callbackException->isNop())
                                $callbackException->invoke($e);
                            else
                                throw $e;
                        }
                    }
                }
            }
        }
        return !$fail;
    }

    public function analyze($incremental = true, $ignoreCache = false,
                            Callback $callbackException = null, Callback $callbackScan = null){
        $this->dependClasses = array();

        $upd = $this->meta['$$$upd'];
        if ($ignoreCache || (!$upd || $this->directory->isModified($upd))){
            $files = $this->directory->findFiles(true);
            if($success = $this->analyzeFiles($files, $incremental, $ignoreCache, $callbackException, $callbackScan)){

                if (!$ignoreCache)
                    $success = $this->analyzeFiles(
                        $this->getDependFiles(), $incremental, true, $callbackException, $callbackScan
                    );

                if ($success){
                    $this->meta['$$$upd'] = $this->directory->lastModified();
                    $this->saveMeta();
                }
            }
        }
    }
}
