<?php
namespace regenix\analyze\analyzers;

use regenix\analyze\Analyzer;
use regenix\analyze\exceptions\UseAnalyzeException;
use regenix\lang\ClassScanner;

class UseAnalyzer extends Analyzer {

    public function walk(\PHPParser_Node $node){
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
