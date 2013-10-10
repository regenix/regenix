<?php
namespace regenix\analyze\analyzers;

use regenix\analyze\AnalyzeManager;
use regenix\analyze\Analyzer;
use regenix\analyze\visitors\DisableNodeVisitor;
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
