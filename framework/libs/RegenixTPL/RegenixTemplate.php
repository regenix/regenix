<?php
namespace framework\libs\RegenixTPL;

/**
 * Class RegenixTemplate
 * @package framework\libs\RegenixTPL
 */
use framework\exceptions\CoreException;
use framework\mvc\template\TemplateLoader;

/**
 *  ${var} - var
 *  {tag /} - tag
 *  {tag} #{/tag} - big tag
 *  &{message} - i18n
 *  {if} ... {/if}
 *  {else} {/else}
 *  {tag arg, name: value, name: value /}
 *  @{Application.index} - route
 */
class RegenixTemplate {

    const type = __CLASS__;

    /** @var string */
    protected $tmpDir;

    /** @var string[] */
    protected $tplDirs;

    /** @var RegenixTemplateTag[string] */
    protected $tags;

    /** @var string */
    protected $file;

    /** @var array */
    public $blocks;

    /** @var string */
    protected $compiledFile;

    public function __construct(){

        $this->registerTag(new RegenixGetTag());
        $this->registerTag(new RegenixSetTag());
    }

    public function setFile($file){
        $this->file = $file;
    }

    public function setTempDir($directory){
        $this->tmpDir = $directory;
    }

    public function setTplDirs(array $dirs){
        $this->tplDirs = $dirs;
    }

    public function registerTag(RegenixTemplateTag $tag){
        $this->tags[strtolower($tag->getName())] = $tag;
    }

    public function __clone(){
        $new = new RegenixTemplate();
        $new->setTplDirs($this->tplDirs);
        $new->setTempDir($this->tmpDir);
        $new->tags   = $this->tags;
        $new->blocks =& $this->blocks;

        return $new;
    }

    protected function _makeArgs($str){
        if ( strpos($str, ':') === false ){
            return 'array("_arg" => ' . $str . ')';
        } else {
            $result = 'array(';
            $args = explode(',', $str, 100);
            foreach($args as $arg){
                $tmp = explode(':', $arg);
                $key = trim($tmp[0]);
                $result .= "'" . $key . "' => (" . $tmp[1] . '), ';
            }
            return $result . ')';
        }
    }

    protected function _compile(){
        $source = file_get_contents($this->file);
        $result = '<?php use ' . self::type . ';' . "\n";

        $result .= "\n" . '?>';
        $p = -1;
        $lastE = -1;
        while(($p = strpos($source, '{', $p + 1)) !== false){

            $mod  = $source[$p - 1];
            $e    = strpos($source, '}', $p);
            $expr = substr($source, $p + 1, $e - $p - 1);

            $prevSource = $lastE === -2 ? '' : substr($source, $lastE + 1, $p - $lastE - 2);
            $lastE = $e;

            $result .= $prevSource;
            $extends = false;
            switch($mod){
                case '@': {
                    $result .= '<?php echo ' . $expr . '?>';
                } break;
                case '#': {
                    $tmp = explode(' ', $expr, 2);
                    $cmd = $tmp[0];
                    if ($cmd[0] == '/')
                        $result .= '<?php end'.substr($cmd,1).'?>';
                    else {
                        if ( $cmd === 'else' )
                            $result .= '<?php else:?>';
                        elseif ($cmd === 'extends'){
                            $result .= '<?php echo $_TPL->_renderBlock("doLayout", ' . $tmp[1] . '); $__extends = true;?>';
                            $extends = true;
                        } elseif ($cmd === 'doLayout'){
                            $result .= '%__BLOCK_doLayout__%';
                        } elseif ($this->tags[$cmd]){
                            $result .= '<?php $_TPL->_renderTag("' . $cmd . '", '.$this->_makeArgs($tmp[1]).');?>';
                        } else
                            $result .= '<?php ' .$cmd. '(' . $tmp[1] . '):?>';
                    }
                } break;
                default: {
                    $result .= $mod;
                    if ( ($xp = strpos($expr, '->')) !== false ){
                        $result .= '<?php echo $_TPL->_makeObjectVar(' . substr($expr, 0, $xp) . ')'
                            . substr($expr, $xp) . '?>';
                    } else {
                        $result .= '<?php echo $_TPL->_renderVar(' . $expr . ')?>';
                    }
                } break;
            }
        }
        $result .= substr($source, $lastE + 1);
        $result .= '<?php if($__extends){ $_TPL->_renderContent(); } ?>';

        $dir = dirname($this->compiledFile);
        if (!is_dir($dir))
            mkdir($dir, 0777, true);

        file_put_contents($this->compiledFile, $result);
    }

    public function compile($cached = true){
        $sha = sha1($this->file);
        $this->compiledFile = ($this->tmpDir . '/' . $sha . '.' . filemtime($this->file) . '.php');
        if ( IS_DEV ){
            foreach(glob($this->tmpDir . '/' . $sha . '.*.php') as $file){
                $file = realpath($file);
                if ( $file == $this->compiledFile ) continue;
                @unlink($file);
            }
        }

        if ( !is_file($this->compiledFile) || !$cached ){
            $this->_compile();
        }
    }

    public function render($args, $cached = true){
        $this->compile($cached);
        $_tags = $this->tags;
        $_TPL = $this;
        if ($args)
            extract($args, EXTR_PREFIX_INVALID | EXTR_OVERWRITE, 'arg_');
        include $this->compiledFile;
    }

    public function _renderVar($var){
        if (is_object($var)){
            return (string)$var;
        } else
            return htmlspecialchars($var);
    }

    public function _makeObjectVar($var){
        return RegenixVariable::current($var);
    }

    public function _renderTag($tag, array $args = array()){
        $this->tags[$tag]->call($args, $this);
    }

    public function _renderBlock($block, $file, array $args = null){
        $tpl = clone $this;
        $tpl->setFile( TemplateLoader::findFile(str_replace('.', '/',$file) . '.html') );
        ob_start();
            $tpl->render($args);
            $str = ob_get_contents();
        ob_end_clean();
        $this->blocks[ $block ] = $str;
        ob_start();
    }

    public function _renderContent(){
        $content = ob_get_contents();
        ob_end_clean();
        $content = str_replace('%__BLOCK_doLayout__%', $content, $this->blocks['doLayout']);
        foreach($this->blocks as $name => $block){
            if ($name != 'doLayout'){
                $content = str_replace('%__BLOCK_'.$name.'__%', $block, $content);
            }
        }

        echo $content;
    }
}

abstract class RegenixTemplateTag {

    abstract function getName();
    abstract public function call($args, RegenixTemplate $ctx);
}

class RegenixGetTag extends RegenixTemplateTag {

    function getName(){
        return 'get';
    }

    public function call($args, RegenixTemplate $ctx){
        echo '%__BLOCK_' . $args['_arg'] . '__%';
    }
}

class RegenixSetTag extends RegenixTemplateTag {

    function getName(){
        return 'set';
    }

    public function call($args, RegenixTemplate $ctx){
        list($key, $value) = each($args);
        $ctx->blocks[$key] = $value;
    }
}

class RegenixVariable {

    protected $var;
    protected static $instance;
    protected static $modifiers = array();

    protected function __construct($var){
        $this->var = $var;
    }

    public function raw(){
        return $this->var;
    }

    public function format($format){
        $this->var = date($format, $this->var);
        return $this;
    }

    public static function current($var){
        if (self::$instance){
            self::$instance->var = $var;
            return self::$instance;
        }
        return self::$instance = new RegenixVariable($var);
    }

    public function __toString(){
        return (string)$this->var;
    }

    public function __call($name, $args){
        $name = strtolower($name);
        if ($callback = self::$modifiers[$name]){
            array_unshift($args, $this->var);
            $this->var = call_user_func_array($callback, $args);
            return $this->var;
        } else
            throw CoreException::formated('Template `%s()` modifier not found', $name);
    }
}