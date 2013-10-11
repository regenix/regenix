<?php
namespace regenix\analyze\analyzers;

use regenix\analyze\AnalyzeManager;
use regenix\analyze\Analyzer;
use regenix\analyze\exceptions\StaticAnalyzeException;
use regenix\lang\DI;
use regenix\lang\File;

class RegenixAnalyzer extends StaticAnalyzer {

    private $functions = array();
    private $staticMethods = array();

    public function __construct(AnalyzeManager $manager, File $file, array $statements, $content) {
        parent::__construct($manager, $file, $statements, $content);
        $this->addFunction('cl', array(0), true);
        $this->addStaticMethod(DI::type, 'getInstance', array(0), false);
        $this->addStaticMethod(DI::type, 'getSingleton', array(0), false);
        $this->addStaticMethod(DI::type, 'bindTo', array(0, 1), false);
    }


    private function addFunction($name, array $args, $strict = false){
        $name = strtolower($name);
        $this->functions[$name] = array($name, 'args' => $args, 'strict' => $strict);
    }

    private function addStaticMethod($class, $method, array $args, $strict = false){
        $name = strtolower($class .'::'. $method);
        $this->staticMethods[$name] = array($name, 'args' => $args, 'strict' => $strict);
    }

    private function check_funcCall(\PHPParser_Node_Expr_FuncCall $node, array $args, $strict = false){
        $name = $node->name->toString();

        foreach($args as $one){
            $arg = $node->args[$one];
            if ($arg){
                if ($arg->value instanceof \PHPParser_Node_Scalar_String){
                    $value = $arg->value->value;
                    $value = str_replace('.', '\\', $value);
                    if ($value[0] === '\\')
                        $value = substr($value, 1);

                    $this->checkClassExists($value, $node);
                } elseif ($strict) {
                    throw new StaticAnalyzeException($this->file, $node->getLine(),
                        'Argument %s of `%s()` function must be scalar string value',
                        $one, $name
                    );
                }
            }
        }
    }

    private function check_staticCall(\PHPParser_Node_Expr_StaticCall $node, array $args, $strict = false){
        $name = $node->class->toString();
        $method = $node->name;

        foreach($args as $one){
            $arg = $node->args[$one];
            if ($arg){
                if ($arg->value instanceof \PHPParser_Node_Scalar_String){
                    $value = $arg->value->value;
                    $value = str_replace('.', '\\', $value);
                    if ($value[0] === '\\')
                        $value = substr($value, 1);

                    $this->checkClassExists($value, $node);
                } elseif ($strict) {
                    throw new StaticAnalyzeException($this->file, $node->getLine(),
                        'Argument %s of `%s::%s()` method must be scalar string value',
                        $one, $name, $method
                    );
                }
            }
        }
    }

    public function walk(\PHPParser_Node $node){
        if($node instanceof \PHPParser_Node_Expr_FuncCall){
            $name = $node->name;
            if ($name instanceof \PHPParser_Node_Name){
                $funcName = strtolower($name->toString());
                if ($funcName[0] === '\\')
                    $funcName = substr($funcName, 1);

                $info = $this->functions[$funcName];
                if ($info){
                    $this->check_funcCall($node, $info['args'], $info['strict']);
                }
            }
        } elseif ($node instanceof \PHPParser_Node_Expr_StaticCall){
            $name = $node->class;
            if ($name instanceof \PHPParser_Node_Name){
                $fullName = strtolower($name->toString());
                $method = $node->name;
                if ($method instanceof \PHPParser_Node_Expr)
                    return;
                $method = strtolower($method);

                $info = $this->staticMethods[$fullName . '::' . $method];
                if ($info){
                    $this->check_staticCall($node, $info['args'], $info['strict']);
                }
            }
        }
    }
}