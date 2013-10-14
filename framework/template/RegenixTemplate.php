<?php
namespace regenix\template;

/**
 * Class RegenixTemplate
 * @package regenix\libs
 */
    use regenix\core\Regenix;
    use regenix\exceptions\TemplateException;
    use regenix\exceptions\TemplateNotFoundException;
    use regenix\lang\ClassScanner;
    use regenix\lang\CoreException;
    use regenix\lang\DI;
    use regenix\lang\String;
    use regenix\i18n\I18n;
    use regenix\mvc\http\Request;
    use regenix\mvc\http\session\Flash;
    use regenix\mvc\http\session\Session;
    use regenix\mvc\template\TemplateLoader;
    use regenix\template\tags\RegenixTagAdapter;

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

    private static $openChar = '{';
    private static $closeChar = '}';

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

    /** @var RegenixTemplateTag[] */
    protected $tags;

    /** @var RegenixTemplateFilter[] */
    protected $filters;

    /** @var string */
    protected $file;

    /** @var string */
    protected $root;

    /** @var string[] */
    protected $uses;

    /** @var array */
    public $blocks;

    /** @var string */
    protected $compiledFile;

    public function __construct($autoRegister = true){
        if ($autoRegister){
            $meta = ClassScanner::find(RegenixTemplateTag::regenixTemplateTag_type);
            foreach($meta->getAllChildren() as $class){
                if (!$class->isAbstract()){
                    $instance = DI::getInstance($class->getName());
                    $this->registerTag($instance);
                }
            }

            $meta = ClassScanner::find(RegenixTemplateFilter::regenixTemplateFilter_type);
            foreach($meta->getAllChildren() as $class){
                if (!$class->isAbstract())
                    $this->registerFilter(DI::getInstance($class->getName()));
            }
        }
        $this->addUse(RegenixTagAdapter::type, 'TPL');
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

    public function addUse($class, $as = false){
        if ($class[0] === '\\')
            $class = substr($class, 1);

        $this->uses[$class] = $as ? $as : $class;
    }

    /**
     * @return array
     */
    public function getArgs(){
        return $this->args;
    }

    /**
     * @param $name
     * @param $value
     */
    public function putArg($name, $value){
        $this->args[$name] = $value;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getArg($name){
        return $this->args[$name];
    }

    public function registerTag(RegenixTemplateTag $tag){
        $name = strtolower($tag->getName());
        if (!isset($this->tags[$name])){
            $this->tags[$name] = $tag;
            return true;
        }

        return false;
    }

    public function registerFilter(RegenixTemplateFilter $filter){
        $name = strtolower($filter->getName());
        if (!isset($this->filters[$name])){
            $this->filters[$name] = $filter;
            return true;
        }

        return false;
    }

    public function duplicate(){
        $new = new RegenixTemplate(false);
        $new->setTplDirs($this->tplDirs);
        $new->setTempDir($this->tmpDir);
        $new->tags    = $this->tags;
        $new->filters = $this->filters;
        $new->blocks  =& $this->blocks;
        $new->uses    = $this->uses;

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

        $str = '<?php ';
        if ($this->uses){
            foreach($this->uses as $use => $as){
                $str .= String::format('use %s as %s; ', $use, $as);
            }
        }
        $str .= ' ?>';

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
                if ($ch == '{' && $source[$i] == '{' && !$openTag){
                    $str .= $source[$i];
                    $i++;
                    continue;
                }
                if ($sk == 0){
                    switch($source[$i-2]){
                        case '#':
                        case '_':
                        case '%':
                        case '@':
                        case '\\':
                            $mod = $source[$i-2];
                    }
                    $lastE = $i;
                }
                $sk += 1;
            }

            if ( ($ch == '}' && !$openTag) || ($ch == '}' && $source[$i] == '}' && $openTag) ){
                if ($ch == '}' && $source[$i] == '}' && !$openTag){
                    $str .= $source[$i];
                    $i++;
                    //var_dump($str);
                    continue;
                }

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
                        case '\\': {
                            $str .= '{' . $expr . '}';
                        } break;
                        case '%': {
                            $str .= '<?php ' . $expr . ' ?>';
                        } break;
                        case '@': {
                            $str .= '<?php echo ' . $expr . ' ?>';
                        } break;
                        case '_': {
                            $str .= '<?php echo htmlspecialchars(\\regenix\\i18n\\I18n::get('. $expr .'))?>';
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
                                    if ($cmd === 'foreach'){
                                        $varName = substr($tmp[1], strrpos($tmp[1], ' ') + 1);
                                        $arrayName = substr($tmp[1], 0, strrpos($tmp[1], 'as'));

                                        $str .= '<?php ' . $varName . '_id = -1; '
                                               . $varName . '_count = count(' . $arrayName . ');';

                                        $str .= $cmd . '(' . $tmp[1] . '): ';
                                        $str .= 'if (' . $varName . '_count) {';
                                        $str .= $varName . '_id++; ';
                                        $str .= $varName . '_isFirst = ' . $varName . '_id === 0; ';
                                        $str .= $varName . '_isLast = ' . $varName . '_id === ' . $varName . '_count - 1; ';
                                        $str .= '}';

                                        $str .= ' ?>';

                                    } else {
                                        $str .= '<?php ' . $cmd . '(' . $tmp[1] . '):?>';
                                    }
                                } elseif (String::startsWith($cmd, 'tag.')){
                                    $str .= '<?php $_TPL->renderHtmlTag("' . substr($cmd, 4) . '", ' . $this->_makeArgs($tmp[1]) . ');?>';
                                } elseif ($this->tags[$cmd]){
                                    $str .= '<?php $_TPL->renderTag("' . $cmd . '", '.$this->_makeArgs($tmp[1]).');?>';
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
        if ( REGENIX_IS_DEV ){
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

    /**
     * @param $__args
     * @param bool $__cached
     * @param bool $return
     * @return string
     * @throws \Exception
     */
    public function render($__args, $__cached = true, $return = false){
        $this->compile($__cached);
        $this->args = $__args;
        $_tags = $this->tags;
        $_TPL  = $this;

        if ($__args)
            extract($__args, EXTR_PREFIX_INVALID | EXTR_OVERWRITE, 'arg_');

        CoreException::setMirrorFile($this->compiledFile, $this->file);
        CoreException::setMirrorOffsetLine($this->compiledFile, 1);

        ob_start();
        try {
            include $this->compiledFile;
            $content = ob_get_contents();
            ob_end_clean();
            if ($return)
                return $content;
            else
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
            return RegenixTemplateVariable::current($var, $this);
    }

    public function renderTag($tag, array $args = array(), $return = false){
        if ($return)
            return $this->tags[$tag]->call($args, $this);
        else
            echo $this->tags[$tag]->call($args, $this);
    }

    public function callFilter($name, $value, array $args = array()){
        if($filter = $this->filters[strtolower($name)]){
            return $filter->call($value, $args, $this);
        } else
            throw new TemplateException('Template filter `%s()` is not found', $name);
    }

    public function renderHtmlTag($tag, array $args = array()){
        $tpl     = $this->duplicate();
        $tplFile = '.tags/' . str_replace('.', '/', $tag) . '.html';

        $args['flash'] = DI::getInstance(Flash::type);
        $args['request'] = DI::getInstance(Request::type);
        $args['session'] = DI::getInstance(Session::type);

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
            $name = strtolower($name);
            if ($name != 'content'){
                $content = str_replace('%__BLOCK_'.$name.'__%', $block, $content);
            }
        }

        echo $content;
    }
}
