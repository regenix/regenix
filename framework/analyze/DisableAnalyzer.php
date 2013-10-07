<?php
namespace regenix\analyze;

use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\lang\String;

class DisableAnalyzer extends Analyzer {

    /** @var array */
    protected $disableFeatures;

    /** @var array */
    protected $disableGlobals;

    /** @var array */
    protected $disableFunctions;

    public static $GLOBALS = array(
        'GLOBALS' => 1,
        '_REQUEST' => 1,
        '_POST' => 1,
        '_GET' => 1,
        '_SESSION' => 1,
        '_COOKIE' => 1,
        '_ENV' => 1,
        '_SERVER' => 1,
        '_FILES' => 1
    );

    public function __construct(AnalyzeManager $manager, File $file, array $statements, $content){
        parent::__construct($manager, $file, $statements, $content);
        $config = $manager->getConfiguration();
        $this->disableFeatures = array_map('strtolower', $config->getArray('disable.features'));
        $this->disableFunctions = $config->getArray('disable.functions');
        $this->disableFunctions = array_map(function($item){
            $item = strtolower(str_replace('.', '\\', $item));
            if ($item[0] === '\\')
                $item = substr($item, 1);
            return $item;
        }, $this->disableFunctions);

        $this->disableGlobals = $config->getArray('disable.globals');
        $this->disableGlobals = array_map(function($item){
            if ($item[0] === '$')
                $item = substr($item, 1);

            if (!isset(DisableAnalyzer::$GLOBALS[$item]))
                throw new CoreException('The "%s" is not the name of a global var', $item);

            return $item;
        }, $this->disableGlobals);
    }

    public function getSort(){
        return 10000;
    }

    public function analyze() {
        $traverser = new \PHPParser_NodeTraverser();
        $traverser->addVisitor(new DisableNodeVisitor(
            $this->file,
            $this->disableFeatures, $this->disableGlobals, $this->disableFunctions
        ));
        $traverser->traverse($this->statements);
    }
}

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

class DisableAnalyzeException extends AnalyzeException {}