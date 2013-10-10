<?php
namespace regenix\analyze\visitors;

use regenix\analyze\exceptions\UseAnalyzeException;
use regenix\lang\ClassScanner;
use regenix\lang\File;

class UseNodeVisitor extends \PHPParser_NodeVisitorAbstract {

    /** @var File */
    protected $file;

    public function __construct($file){
        $this->file = $file;
    }

    public function leaveNode(\PHPParser_Node $node) {
        if ($node instanceof \PHPParser_Node_Stmt_Use){
            foreach($node->uses as $one){
                $class = $one->name->toString();
                $info = ClassScanner::find($class);
                if (!$info && !class_exists($class, false))
                    throw new UseAnalyzeException(
                        $this->file,
                        $one->getLine(),
                        'Class "%s" is not found in the use statement', $class
                    );
            }
        }
    }
}