<?php
namespace regenix\analyze;

use regenix\lang\ClassScanner;
use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\lang\String;

abstract class Analyzer {

    const type = __CLASS__;

    /** @var File */
    protected $file;

    /** @var AnalyzeManager */
    protected $manager;

    /** @var \PHPParser_NodeAbstract[] */
    protected $statements;

    /** @var string */
    protected $content;

    public function __construct(AnalyzeManager $manager, File $file, array $statements, $content){
        $this->file = $file;
        $this->content = $content;
        $this->manager = $manager;
        $this->statements = $statements;
    }

    abstract function analyze();

    public function getSort(){
        return 0;
    }

    protected static $methods = array();

    public static function getMethods($className){
        $methods = self::$methods[$className];
        if (isset($methods))
            return $methods;
        self::$methods[$className] = true;

        if (class_exists($className, false)
            || interface_exists($className, false)
            || trait_exists($className, false)){
            $reflection = new \ReflectionClass($className);
            $methods = $reflection->getMethods();
            $result = array();
            foreach($methods as $method){
                $result[$method->getName()] = array(
                    'class' => $className,
                    'name' => $method->getName(),
                    'abstract' => $method->isAbstract(),
                    'final' => $method->isFinal(),
                    'static' => $method->isStatic()
                );
            }
            return self::$methods[$className] = $result;
        }

        $meta = ClassScanner::find($className);
        if ($meta){
            $file = $meta->getFile();
            $parser = new \PHPParser_Parser(new \PHPParser_Lexer_Emulative());
            try {
                $content = $file->getContents();
                $statements = $parser->parse($content);
            } catch (\PHPParser_Error $e) {
                throw new ParseException($file, $e->getRawLine(), $e->getMessage());
            }

            $traverser = new \PHPParser_NodeTraverser();
            $traverser->addVisitor(new \PHPParser_NodeVisitor_NameResolver());
            $traverser->addVisitor($visitor = new AnalyzeNodeVisitor());
            $traverser->traverse($statements);

            foreach($visitor->getResult() as $name => $one){
                $m = array();
                foreach($one as $key => $value){
                    if ($key[0] === '$'){
                        $m = array_merge($m, self::getMethods(substr($key, 1)));
                    } else {
                        $m[$key] = $value;
                    }
                }
                self::$methods[$name] = $m;
            }

            return self::$methods[$className];
        } else {
            return self::$methods[$className] = array();
        }
    }
}

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

class AnalyzeException extends CoreException {

    /** @var File */
    protected $file;

    /** @var int */
    protected $line;

    public function __construct(File $file, $line, $message){
        $this->file = $file;
        $this->line = $line;

        $args = array();
        if (func_num_args() > 3)
            $args = array_slice(func_get_args(), 3);

        parent::__construct(String::formatArgs($message, $args));
    }

    public function getSourceLine(){
        return $this->line;
    }

    public function getSourceFile(){
        return $this->file->getPath();
    }

    public function isHidden(){
        return true;
    }
}

class ParseException extends AnalyzeException {}