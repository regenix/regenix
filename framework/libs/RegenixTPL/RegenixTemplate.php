<?php
namespace framework\libs\RegenixTPL;

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

/**
 * Class RegenixTemplate
 * @package framework\libs\RegenixTPL
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

    protected function parse(){

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

            $prevSource = substr($source, $lastE + 1, $p - $lastE - 2);
            $lastE = $e;

            $result .= $prevSource;
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
                        else
                            $result .= '<?php ' .$cmd. '(' . $tmp[1] . '):?>';
                    }
                } break;
                default: {
                    $result .= $mod . '<?php echo RegenixTemplate::renderVar(' . $expr . ')?>';
                } break;
            }
        }
        $result .= substr($source, $lastE + 1);

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
        $tags = $this->tags;
        extract($args, EXTR_PREFIX_INVALID | EXTR_OVERWRITE, 'arg_');

        include $this->compiledFile;
    }

    public static function renderVar($var){
        return htmlspecialchars($var);
    }
}

abstract class RegenixTemplateTag {

    abstract function getName();
}