<?php
namespace regenix\analyze;

use regenix\lang\ClassScanner;
use regenix\lang\File;

class UseAnalyzer extends Analyzer {

    public function analyze() {
        $traverser = new \PHPParser_NodeTraverser();
        $traverser->addVisitor(new UseNodeVisitor($this->file));
        $traverser->traverse($this->statements);
    }
}

class UseNodeVisitor extends \PHPParser_NodeVisitorAbstract{

    /** @var File */
    protected $file;

    public function __construct($file){
        $this->file = $file;
    }

    public function leaveNode(\PHPParser_Node $node) {
        if ($node instanceof \PHPParser_Node_Stmt_Use){
            //dump($node->uses);
            foreach($node->uses as $one){
                $class = $one->name->toString();
                $info = ClassScanner::find($class);
                if (!$info && !class_exists($class, false))
                    throw new UseAnalyzeException(
                        $this->file,
                        $one->getLine(),
                        'Class "%s" not found in the use statement', $class
                    );
            }
        }
    }
}

class UseAnalyzeException extends AnalyzeException {}