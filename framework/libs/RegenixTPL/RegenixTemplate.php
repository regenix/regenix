<?php
namespace framework\libs\RegenixTPL;

/**
 * Class RegenixTemplate
 * @package framework\libs\RegenixTPL
 */
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
    protected $blocks;

    /** @var string */
    protected $compiledFile;

    public function __construct(){
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
        $new->tags = $this->tags;

        return $new;
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
                        } else
                            $result .= '<?php ' .$cmd. '(' . $tmp[1] . '):?>';
                    }
                } break;
                default: {
                    $result .= $mod . '<?php echo $_TPL->_renderVar(' . $expr . ')?>';
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
        return htmlspecialchars($var);
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
        echo $content;
    }
}

abstract class RegenixTemplateTag {

    abstract function getName();
}