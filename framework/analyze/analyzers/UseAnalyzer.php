<?php
namespace regenix\analyze\analyzers;

use regenix\analyze\Analyzer;
use regenix\analyze\visitors\UseNodeVisitor;
use regenix\lang\ClassScanner;

class UseAnalyzer extends Analyzer {

    public function analyze() {
        $traverser = new \PHPParser_NodeTraverser();
        $traverser->addVisitor(new UseNodeVisitor($this->file));
        $traverser->traverse($this->statements);
    }
}
