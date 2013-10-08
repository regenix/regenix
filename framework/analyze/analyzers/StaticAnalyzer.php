<?php
namespace regenix\analyze\analyzers;

use regenix\analyze\Analyzer;
use regenix\analyze\visitors\StaticNodeVisitor;
use regenix\lang\ClassScanner;

class StaticAnalyzer extends Analyzer {

    public function getSort() {
        return 100;
    }

    public function analyze() {
        $traverser = new \PHPParser_NodeTraverser();
        $traverser->addVisitor(new \PHPParser_NodeVisitor_NameResolver());
        $traverser->addVisitor(new StaticNodeVisitor($this->file));
        $traverser->traverse($this->statements);
    }
}
