<?php
namespace regenix\analyze\visitors;

use regenix\analyze\exceptions\DisableAnalyzeException;
use regenix\lang\File;
use regenix\lang\String;

class DisableNodeVisitor extends \PHPParser_NodeVisitorAbstract{

    /** @var File */
    protected $file;

    /** @var array */
    protected $disableFeatures;
    protected $disableGlobals;
    protected $disableFunctions;

    public function __construct($file, array $disableFeatures, array $disableGlobals,
                                array $disableFunctions){
        $this->file = $file;
        $this->disableFeatures = array_combine($disableFeatures, $disableFeatures);
        $this->disableGlobals = array_combine($disableGlobals, $disableGlobals);
        $this->disableFunctions = array_combine($disableFunctions, $disableFunctions);
    }

    public function leaveNode(\PHPParser_Node $node) {
        if ($node instanceof \PHPParser_Node_Expr_Variable){
            $name = $node->name;
            if ($this->disableGlobals[$name]){
                throw new DisableAnalyzeException(
                    $this->file,
                    $node->getLine(),
                    'Forbidden usage of the super-global var "$%s", see `disable.globals` in `conf/analyzer.conf`',
                    $name
                );
            }
        } else if ($node instanceof \PHPParser_Node_Stmt_Global){
            if ($this->disableFeatures['globals'] || $this->disableFeatures['global'])
                throw new DisableAnalyzeException(
                    $this->file,
                    $node->getLine(),
                    'Forbidden usage of global variables, see `disable.features` in configuration'
                );
        } else if ($node instanceof \PHPParser_Node_Stmt_Function){
            if ($this->disableFeatures['functions'] || $this->disableFeatures['function']){
                if ($node->name){
                    throw new DisableAnalyzeException(
                        $this->file,
                        $node->getLine(),
                        'Forbidden usage of simple named functions, see `disable.features` in configuration'
                    );
                }
            }
        } else if ($node instanceof \PHPParser_Node_Stmt_Goto){
            if ($this->disableFeatures['goto']){
                throw new DisableAnalyzeException(
                    $this->file,
                    $node->getLine(),
                    'Forbidden usage of GOTO statement, see `disable.features` in configuration'
                );
            }
        } else if ($node instanceof \PHPParser_Node_Expr_FuncCall){
            if ($node->name && $node instanceof \PHPParser_Node_Name){

                $name = $node->name->toString();
                $name = str_replace('.', '\\', strtolower($name));
                if ($name[0] === '\\')
                    $name = substr($name, 1);

                if ($this->disableFunctions[$name])
                    throw new DisableAnalyzeException(
                        $this->file,
                        $node->getLine(),
                        'Forbidden usage of the "%s()" function, see `disable.functions` in configuration',
                        $name
                    );

                foreach($this->disableFunctions as $one){
                    if (substr($one, -1) === '*')
                        if (String::startsWith($name, substr($one, 0, -1))){
                            throw new DisableAnalyzeException(
                                $this->file,
                                $node->getLine(),
                                'Forbidden usage of the "%s()" function, see "%s" pattern in `disable.functions` in configuration',
                                $name, $one
                            );
                        }
                }
            }
        }
    }
}
