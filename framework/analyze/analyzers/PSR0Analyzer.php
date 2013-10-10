<?php
namespace regenix\analyze\analyzers;

use regenix\analyze\AnalyzeManager;
use regenix\analyze\Analyzer;
use regenix\analyze\exceptions\PSR0AnalyzeException;
use regenix\analyze\visitors\PSR0NodeVisitor;
use regenix\lang\File;
use regenix\lang\String;

class PSR0Analyzer extends Analyzer {

    protected $exclude;
    protected $enable;
    protected $prefix;

    public function __construct(AnalyzeManager $manager, File $file, array $statements, $content){
        parent::__construct($manager, $file, $statements, $content);
        $config = $manager->getConfiguration();
        $this->enable = $config->getBoolean('psr0.enable');
        $this->exclude = $config->getArray('psr0.exclude');
        $this->prefix = str_replace('.', '\\', $config->getString('psr0.prefix'));
        $this->exclude = array_map('trim', $this->exclude);
    }

    public function getSort(){
        return -1;
    }

    public function analyze(){
        if (!$this->enable)
            return;

        $statement = $this->statements[0];
        if (!($statement instanceof \PHPParser_Node_Stmt_Namespace)){
            throw new PSR0AnalyzeException(
                $this->file, $statement->getLine(), 'You should always use namespaces in PHP sources'
            );
        }

        $name = $statement->name;
        if ($name)
            $name = $name->toString();

        foreach($this->exclude as $one){
            $one = str_replace('.', '\\', $one);
            if (String::startsWith($name, $one))
                return;
        }

        $base = $this->manager->getDirectory()->getPath();
        if (substr($base, -1) === '/')
            $base = substr($base, 0, -1);

        $current = str_replace(array($base, '/'), array('', '\\'), $this->file->getParent());
        if ($current[0] === '\\')
            $current = substr($current, 1);

        if ($this->prefix)
            $current = $this->prefix . ($current ? '\\' . $current : '');

        if ($name !== $current){
            throw new PSR0AnalyzeException(
                $this->file,
                $statement->getLine(),
                'PSR-0 Standard: Incorrect namespace, it should be `%s` instead of `%s`',
                $current, $name
            );
        }

        $traverser = new \PHPParser_NodeTraverser();
        $traverser->addVisitor(new PSR0NodeVisitor($this->file, $this->prefix));
        $traverser->traverse($this->statements);
    }
}

