<?php
namespace regenix\analyze\visitors;

use regenix\analyze\Analyzer;

class AnalyzeNodeVisitor extends \PHPParser_NodeVisitorAbstract {
    /** @var array */
    public $result;

    /** @var \PHPParser_Node_Stmt */
    public $current;

    public function leaveNode(\PHPParser_Node $node) {
        if ($node instanceof \PHPParser_Node_Stmt_Class
            || $node instanceof \PHPParser_Node_Stmt_Interface
            || $node instanceof \PHPParser_Node_Stmt_Trait){
            $this->current = $node;
            $className = $node->namespacedName;
            if ($className instanceof \PHPParser_Node_Name) $className = $className->toString();

            $methods = array();
            if ($node instanceof \PHPParser_Node_Stmt_Class
                || $node instanceof \PHPParser_Node_Stmt_Interface){
                $extend = $node->extends;

                if ($extend){
                    $name = $extend->toString();
                    $tmp = Analyzer::getMethods($name);
                    if ($tmp !== true)
                        $methods = array_merge($methods, $tmp);
                    else {
                        $methods['$' . $name] = true;
                    }
                }
            }

            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof \PHPParser_Node_Stmt_ClassMethod) {
                    $methods[$stmt->name] = $stmt;
                } else if ($stmt instanceof \PHPParser_Node_Stmt_TraitUse) {
                    foreach($stmt->traits as $trait){
                        $tmp = Analyzer::getMethods($trait->toString());
                        if ($tmp !== true)
                            $methods = array_merge($methods, $tmp);
                        else
                            $methods['$' . $name] = true;
                    }
                }
            }

            foreach($methods as &$method){
                if ($method instanceof \PHPParser_Node_Stmt_ClassMethod){
                    $method = array(
                        'class' => $node->name,
                        'name' => $method->name,
                        'abstract' => $method->isAbstract(),
                        'final' => $method->isFinal(),
                        'static' => $method->isStatic()
                    );
                }
            }
            $this->result[$className] = $methods;
        }
    }

    /**
     * @return array
     */
    public function getResult() {
        return $this->result;
    }
}