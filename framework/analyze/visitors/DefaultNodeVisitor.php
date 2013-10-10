<?php
namespace regenix\analyze\visitors;

use regenix\analyze\Analyzer;
use regenix\lang\File;

class DefaultNodeVisitor extends \PHPParser_NodeVisitorAbstract {

    /** @var Analyzer[] */
    private $analyzers = array();

    /** @var array */
    private $uses = array();

    /** @var File */
    private $file;

    /** @var array */
    private $classes;

    public function __construct(File $file, array $analyzers){
        $this->analyzers = $analyzers;
        $this->file = $file;
    }

    public function leaveNode(\PHPParser_Node $node) {
        foreach($this->analyzers as $analyzer){
            $analyzer->walk($node);
        }

        if ($node instanceof \PHPParser_Node_Stmt_Use){
            foreach($node->uses as $use){
                $this->uses[] = $use->name->toString();
            }
        }
        if ($node instanceof \PHPParser_Node_Stmt_Class
            || $node instanceof \PHPParser_Node_Stmt_Interface
            || $node instanceof \PHPParser_Node_Stmt_Trait){
            $this->classes[] = $node->namespacedName->toString();
        }
    }

    public function getUses(){
        return $this->uses;
    }

    public function getClasses(){
        return $this->classes;
    }
}
