<?php
namespace regenix\analyze\analyzers;

use regenix\analyze\AnalyzeManager;
use regenix\analyze\Analyzer;
use regenix\analyze\exceptions\PSR0AnalyzeException;
use regenix\lang\File;
use regenix\lang\String;

class PSR0Analyzer extends Analyzer {

    protected $exclude;
    protected $enable;
    protected $prefix;
    protected $easing;
    protected $i = 0;

    protected $mainClass;

    public function __construct(AnalyzeManager $manager, File $file, array $statements, $content){
        parent::__construct($manager, $file, $statements, $content);
        $config = $manager->getConfiguration();
        $this->enable = $config->getBoolean('psr0.enabled');
        $this->exclude = $config->getArray('psr0.exclude');
        $this->easing = $config->getBoolean('psr0.easing');
        $this->prefix = str_replace('.', '\\', $config->getString('psr0.prefix'));
        $this->exclude = array_map('trim', $this->exclude);
    }

    public function getSort(){
        return -1;
    }

    public function analyze(){
        if (!$this->enable)
            return;

        $statement = $this->statements[0];
        if (!($statement instanceof \PHPParser_Node_Stmt_Namespace)){
            throw new PSR0AnalyzeException(
                $this->file, $statement->getLine(), 'You should always use namespaces in PHP sources'
            );
        }

        $name = $statement->name;
        if ($name)
            $name = $name->toString();

        foreach($this->exclude as $one){
            $one = str_replace('.', '\\', $one);
            if (String::startsWith($name, $one)) {
                $this->enable = false;
                return;
            }
        }

        $base = $this->manager->getDirectory()->getPath();
        if (substr($base, -1) === '/')
            $base = substr($base, 0, -1);

        $current = str_replace(array($base, '/'), array('', '\\'), $this->file->getParent());
        if ($current[0] === '\\')
            $current = substr($current, 1);

        if ($this->prefix)
            $current = $this->prefix . ($current ? '\\' . $current : '');

        if ((string)$name !== $current){
            throw new PSR0AnalyzeException(
                $this->file,
                $statement->getLine(),
                'PSR-0 Standard: Incorrect namespace, it should be `%s` instead of `%s`',
                $current, $name
            );
        }

        $this->i = 0;
    }

    public function walk(\PHPParser_Node $node){
        if (!$this->enable)
            return;

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
                $this->mainClass = $name;
            }

            if ($this->i > 1){
                if ($this->easing){
                    if (!String::startsWith($name, $this->mainClass)){
                        throw new PSR0AnalyzeException(
                            $this->file,
                            $node->getLine(),
                            'PSR-0: Tha name of "%s" sub class should be "%s", {psr0.easing = on}',
                            $name,
                            $this->mainClass . ucfirst($name)
                        );
                    }
                } else
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

