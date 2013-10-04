<?php
namespace regenix\analyze;

use regenix\lang\File;
use regenix\lang\String;

class PSR0Analyzer extends Analyzer {

    protected $exclude;
    protected $enable;

    public function __construct(AnalyzeManager $manager, File $file, array $statements, $content){
        parent::__construct($manager, $file, $statements, $content);
        $config = $manager->getConfiguration();
        $this->enable = $config->getBoolean('psr0.enable');
        $this->exclude = $config->getArray('psr0.exclude');
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
        $current = str_replace(array($base, '/'), array('', '\\'), $this->file->getParent());

        if ($name !== $current){
            throw new PSR0AnalyzeException(
                $this->file,
                $statement->getLine(),
                'PSR-0 Standard: Incorrect namespace, it should be `%s` instead of `%s`',
                $current, $name
            );
        }

        $traverser = new \PHPParser_NodeTraverser();
        $traverser->addVisitor(new PSR0NodeVisitor($this->file));
        $traverser->traverse($this->statements);
    }
}

class PSR0NodeVisitor extends \PHPParser_NodeVisitorAbstract {

    /** @var File */
    protected $file;

    protected $i;

    public function __construct($file){
        $this->file = $file;
        $this->i = 0;
    }

    public function leaveNode(\PHPParser_Node $node) {
        if ($node instanceof \PHPParser_Node_Stmt_Class){
            $name = $node->name;
            $this->i++;

            if ($this->i === 1){
                $dueName = $this->file->getNameWithoutExtension();
                if ($name !== $dueName){
                    throw new PSR0AnalyzeException(
                        $this->file,
                        $node->getLine(),
                        'PSR-0 Standard: The name of the first class should be "%s" instead of "%s"',
                        $dueName, $name
                    );
                }
            }
        }
    }
}

class PSR0AnalyzeException extends AnalyzeException {}