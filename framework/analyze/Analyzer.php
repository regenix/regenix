<?php
namespace regenix\analyze;

use regenix\analyze\exceptions\ParseAnalyzeException;
use regenix\analyze\visitors\AnalyzeNodeVisitor;
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

    public function analyze(){}
    public function walk(\PHPParser_Node $node){}

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
            || _trait_exists($className, false)){
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
                throw new ParseAnalyzeException($file, $e->getRawLine(), $e->getMessage());
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


