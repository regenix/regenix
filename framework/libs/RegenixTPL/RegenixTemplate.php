<?php
namespace regenix\libs\RegenixTPL {

/**
 * Class RegenixTemplate
 * @package regenix\libs\RegenixTPL
 */
    use regenix\Regenix;
    use regenix\lang\ClassScanner;
    use regenix\lang\CoreException;
    use regenix\lang\String;
    use regenix\libs\I18n;
    use regenix\mvc\Flash;
    use regenix\mvc\Request;
    use regenix\mvc\RequestQuery;
    use regenix\mvc\template\TemplateLoader;
    use regenix\mvc\template\TemplateNotFoundException;

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

    private static $control = array(
        'if' => 1,
        'else' => 1,
        'elseif' => 1,
        'while' => 1,
        'for' => 1,
        'foreach' => 1
    );

    private static $ignoreTags = array(
        'style' => 1,
        'script' => 1
    );

    /** @var array */
    protected $args;

    /** @var string */
    protected $tmpDir;

    /** @var string[] */
    protected $tplDirs;

    /** @var RegenixTemplateTag[string] */
    protected $tags;

    /** @var string */
    protected $file;

    /** @var string */
    protected $root;

    /** @var array */
    public $blocks;

    /** @var string */
    protected $compiledFile;

    public function __construct(){
        $meta = ClassScanner::find(RegenixTemplateTag::type);
        foreach($meta->getChildrensAll() as $class){
            if (!$class->isAbstract())
                $this->registerTag($class->newInstance());
        }
    }

    public function setFile($file){
        $this->file = $file;
    }

    public function setRoot($root){
        $this->root = $root;
    }

    public function setTempDir($directory){
        $this->tmpDir = $directory;
    }

    public function setTplDirs(array $dirs){
        $this->tplDirs = $dirs;
    }

    /**
     * @return array
     */
    public function getArgs(){
        return $this->args;
    }

    public function registerTag(RegenixTemplateTag $tag){
        $name = strtolower($tag->getName());
        if (!isset($this->tags[$name]))
            $this->tags[$name] = $tag;
    }

    public function duplicate(){
        $new = new RegenixTemplate();
        $new->setTplDirs($this->tplDirs);
        $new->setTempDir($this->tmpDir);
        $new->tags   = $this->tags;
        $new->blocks =& $this->blocks;

        return $new;
    }

    protected function _makeArgs($str){
        $args = self::explodeMagic(',', $str, 100);
        $result = 'array(';
        $i = 0;
        foreach($args as $arg){
            $tmp = self::explodeMagic(':', $arg);
            if (sizeof($tmp) === 3){
                unset($tmp[1]);
                $tmp[0] = $arg;
            }
            if ($tmp[1]){
                $key = trim($tmp[0]);
                $result .= "'" . $key . "' =>" . $tmp[1] . ', ';
            } else {
                $result .= "'_arg' => " . $tmp[0] . ', ';
                $i += 1;
            }
        }
        return $result . ')';
    }

    private static function explodeMagic($delimiter, $string){
        $result = array();

        $i     = 0;
        $sk    = 0;
        $skA   = 0;
        $quote = false;
        $quoteT = false;
        $str    = '';
        while($i < strlen($string)){
            $ch = $string[$i];
            $i++;
            $str .= $ch;

            if ( $ch == '"' || $ch == "'" ){
                if($quote){
                    if ($string[$i-1] != '\\'){
                        $quote  = false;
                        $quoteT = false;
                    }
                } else {
                    $quote  = true;
                    $quoteT = $ch;
                }
                continue;
            }
            if ( $ch == '(') $sk += 1;
            if ( $ch == ')' ) $sk -= 1;
            if ( $ch == '[' ) $skA += 1;
            if ( $ch == ']' ) $skA -= 1;

            if ($quote || $sk || $skA)
                continue;

            if ( $ch === $delimiter ){
                $result[] = substr($str, 0, -1);
                $str      = '';
                continue;
            }

        }
        if ($str)
            $result[] = $str;

        return $result;
    }

    protected function _compile(){
        $source = file_get_contents($this->file);
        $result = '<?php $__extends = false; ?>';

        $str = '';
        $i = 0;
        $lastE = -1;

        $sk    = 0;
        $quote = false;
        $quoteT = false;
        $expr   = '';
        $mod    = false;

        $openTag  = false;
        $closeTag = false;
        $inTag   = false;
        $ignoreTag = false;

        while($i < strlen($source)){
            $ch = $source[$i];
            $i++;

            if ($sk === 0){
                $str .= $ch;
            }

            if ( $ch == '"' || $ch == "'" ){
                if($quote){
                    if ($source[$i-1] != '\\'){
                        $quote  = false;
                        $quoteT = false;
                    }
                } else {
                    $quote  = true;
                    $quoteT = $ch;
                }
                continue;
            }

            foreach(self::$ignoreTags as $tag => $k){
                $len = strlen($tag) + 2;
                $tmp = strtolower(substr($source, $i - $len, $len));
                if ($tmp === '<' . $tag . ' ' || $tmp === '<' . $tag . '>'){
                    $openTag = $tag;
                    break;
                }
                if ($tmp === '</' . $tag){
                    if ($openTag === $tag){
                        $openTag = false;
                    }
                }
            }

            if ( ($ch == '{' && !$openTag) || ($ch == '{' && $source[$i] == '{' && $openTag) ){
                if ($sk == 0){
                    switch($source[$i-2]){
                        case '#':
                        case '_':
                        case '%':
                        case '@':
                            $mod = $source[$i-2];
                    }
                    $lastE = $i;
                }
                $sk += 1;
            }

            if ( ($ch == '}' && !$openTag) || ($ch == '}' && $source[$i] == '}' && $openTag) ){
                $sk -= 1;
                if ($openTag)
                    $i++;

                if ($sk == 0){
                    if ($openTag){
                        $expr = String::substring($source, $lastE + 1, $i - 2);
                    } else
                        $expr = String::substring($source, $lastE, $i - 1);

                    $str  = substr($str, 0, $mod ? -2 : -1);

                    switch($mod){
                        case '%': {
                            $str .= '<?php ' . $expr . ' ?>';
                        } break;
                        case '@': {
                            $str .= '<?php echo ' . $expr . ' ?>';
                        } break;
                        case '_': {
                            if ( class_exists('\\regenix\\libs\\I18n') )
                                $str .= '<?php echo htmlspecialchars(\\regenix\\libs\\I18n::get('. $expr .'))?>';
                            else
                                $str .= '<?php echo htmlspecialchars(' . $expr . ')?>';
                        } break;
                        default: {
                            $tmp = explode(' ', $expr, 2);
                            $cmd = $tmp[0];
                            if ($cmd[0] == '/')
                                $str .= '<?php end'.substr($cmd,1).'?>';
                            else {
                                if ( $cmd === 'else' ){
                                    if (trim($tmp[1]))
                                        $str .= '<?php elseif(' . $tmp[1] . '):?>';
                                    else
                                        $str .= '<?php else:?>';
                                } elseif ($cmd === 'extends'){
                                    $str .= '<?php echo $_TPL->_renderBlock("content", ' . $tmp[1] . '); $__extends = true;?>';
                                } elseif ($cmd === 'content'){
                                    $str .= '%__BLOCK_content__%';
                                } elseif (self::$control[$cmd]){
                                    $str .= '<?php ' . $cmd . '(' . $tmp[1] . '):?>';
                                } elseif (String::startsWith($cmd, 'tag.')){
                                    $str .= '<?php $_TPL->_renderHtmlTag("' . substr($cmd, 4) . '", ' . $this->_makeArgs($tmp[1]) . ');?>';
                                } elseif ($this->tags[$cmd]){
                                    $str .= '<?php $_TPL->_renderTag("' . $cmd . '", '.$this->_makeArgs($tmp[1]).');?>';
                                } else {
                                    $data = self::explodeMagic('|', $expr);
                                    if ( $data[1] ){
                                        $mods = self::explodeMagic(',', $data[1]);
                                        foreach($mods as &$mod){
                                            $mod = trim($mod);
                                            if (substr($mod,-1) != ')')
                                                $mod .= '()';
                                        }

                                        $modsAppend = implode('->', $mods);
                                        $str .= '<?php echo $_TPL->_makeObjectVar('. $data[0] . ')->'
                                            . $modsAppend . '?>';
                                    } else {
                                        $str .= '<?php echo htmlspecialchars((string)(' . $data[0] . '))?>';
                                    }
                                }
                            }
                        }
                    }

                    $lastE = 0;
                    $mod   = false;
                }
            }
        }

        $str .= '<?php if($__extends){ $_TPL->_renderContent(); } ?>';

        $dir = dirname($this->compiledFile);
        if (!is_dir($dir))
            mkdir($dir, 0777, true);

        file_put_contents($this->compiledFile, $str);
    }

    public function compile($cached = true){
        $sha = sha1($this->file);
        $this->compiledFile = ($this->tmpDir . $sha . '.' . filemtime($this->file) . '.php');
        if ( IS_DEV ){
            foreach(glob($this->tmpDir . $sha . '.*.php') as $file){
                $file = realpath($file);
                if ( $file == $this->compiledFile ) continue;
                @unlink($file);
            }
        }

        if ( !is_file($this->compiledFile) || !$cached ){
            $this->_compile();
        }
    }

    public function render($__args, $__cached = true){
        $this->compile($__cached);
        $this->args = $__args;
        $_tags = $this->tags;
        $_TPL = $this;
        if ($__args)
            extract($__args, EXTR_PREFIX_INVALID | EXTR_OVERWRITE, 'arg_');

        CoreException::setMirrorFile($this->compiledFile, $this->file);

        ob_start();
        try {
            include $this->compiledFile;
            $content = ob_get_contents();
            ob_end_clean();
            echo $content;
            //ob_end_flush();
        } catch (\Exception $e){
            ob_end_clean();
            throw $e;
        }
    }

    public function _renderVar($var){
        if (is_object($var)){
            return (string)$var;
        } else
            return htmlspecialchars($var);
    }

    public function _makeObjectVar($var){
        if ( is_object($var) )
            return $var;
        else
            return RegenixVariable::current($var);
    }

    public function _renderTag($tag, array $args = array()){
        echo $this->tags[$tag]->call($args, $this);
    }

    public function _renderHtmlTag($tag, array $args = array()){
        $tpl     = $this->duplicate();
        $tplFile = '.tags/' . str_replace('.', '/', $tag) . '.html';

        $args['flash'] = Flash::current();
        $args['request'] = Request::current();
        $args['session'] = Request::current();

        $file = TemplateLoader::findFile($tplFile);
        if (!$file)
            throw new TemplateNotFoundException($tplFile);

        $tpl->setFile( $file );
        $tpl->render($args);
    }

    public function _renderBlock($block, $file, array $args = null){
        $tpl  = $this->duplicate();
        $file = str_replace('\\', '/', $file);
        if (!String::endsWith($file, '.html'))
            $file .= '.html';

        // TODO: refactor
        if ($block === 'content'){
            $args = array_merge($this->args, $args == null ? array() : $args);
        }

        $origin = $file;
        $file   = TemplateLoader::findFile($file);
        if (!$file){
            throw new TemplateNotFoundException($origin);
        }

        $tpl->setFile( $file );
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
        $content = str_replace('%__BLOCK_content__%', $content, $this->blocks['content']);
        foreach($this->blocks as $name => $block){
            if ($name != 'content'){
                $content = str_replace('%__BLOCK_'.$name.'__%', $block, $content);
            }
        }

        echo $content;
    }
}

abstract class RegenixTemplateTag {

    const type = __CLASS__;

    abstract function getName();
    abstract public function call($args, RegenixTemplate $ctx);
}

class RegenixGetTag extends RegenixTemplateTag {

    function getName(){
        return 'get';
    }

    public function call($args, RegenixTemplate $ctx){
        if (isset($args['default']))
            $ctx->blocks[$args['_arg']] = $args['default'];

        return '%__BLOCK_' . $args['_arg'] . '__%';
    }
}

class RegenixSetTag extends RegenixTemplateTag {

    function getName(){
        return 'set';
    }

    public function call($args, RegenixTemplate $ctx){
        foreach($args as $key => $value){
            if ($key === 'content')
                throw CoreException::formated('Block `content` can not be used');

            $ctx->blocks[$key] = $value;
        }
    }
}

class RegenixRenderTag extends RegenixTemplateTag {

    function getName(){
        return 'render';
    }

    protected function render($args, RegenixTemplate $tpl){
        $tplFile = $args['_arg'];

        $args['flash'] = Flash::current();
        $args['request'] = Request::current();
        $args['session'] = Request::current();

        $file = TemplateLoader::findFile($tplFile);
        if (!$file)
            throw new TemplateNotFoundException($tplFile);

        $tpl->setFile( $file );

        ob_start();
        $tpl->render($args);
        $str = ob_get_contents();
        ob_end_clean();

        return $str;
    }

    public function call($args, RegenixTemplate $ctx){
        return $this->render($args, $ctx->duplicate());
    }
}

class RegenixIncludeTag extends RegenixRenderTag {

    function getName(){
        return 'include';
    }

    public function call($args, RegenixTemplate $ctx){
        $args = array_merge($ctx->getArgs(), $args);
        return $this->render($args, $ctx->duplicate());
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
        return $this;
    }

    public function format($format){
        $this->var = date($format, $this->var);
        return $this;
    }

    public function lowerCase(){
        $this->var = strtolower($this->var);
        return $this;
    }

    public function upperCase(){
        $this->var = strtoupper($this->var);
        return $this;
    }

    public function trim(){
        $this->var = trim($this->var);
        return $this;
    }

    public function substring($from, $to = null){
        $this->var = String::substring($this->var, $from, $to);
        return $this;
    }

    public function replace($what, $replace){
        $this->var = str_replace($what, $replace, $this->var);
        return $this;
    }

    public function nl2br(){
        $this->var = nl2br($this->var);
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
}