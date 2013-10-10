<?php
namespace regenix\analyze\visitors;

use regenix\analyze\exceptions\PSR0AnalyzeException;
use regenix\lang\File;

class PSR0NodeVisitor extends \PHPParser_NodeVisitorAbstract {

    /** @var File */
    protected $file;

    /** @var string */
    protected $prefix;

    /** @var int */
    protected $i;

    public function __construct($file, $prefix = ''){
        $this->file = $file;
        $this->i = 0;
        $this->prefix = $prefix;
    }

    public function leaveNode(\PHPParser_Node $node) {
        if ($node instanceof \PHPParser_Node_Stmt_Class){
            $name = $node->name;
            $this->i++;

            if ($this->i === 1){
                $dueName = $this->file->getNameWithoutExtension();

                if ($name !== $dueName){
                    throw new PSR0AnalyzeException(
                        $this->file,
                        $node->getLine(),
                        'PSR-0 Standard: The name of the first class should be "%s" instead of "%s"',
                        $dueName, $name
                    );
                }
            }

            if ($this->i > 1){
                throw new PSR0AnalyzeException(
                    $this->file,
                    $node->getLine(),
                    'PSR-0 Standard: Only one class can be declared in a file, remove "%s" class',
                    $name
                );
            }
        }
    }
}
