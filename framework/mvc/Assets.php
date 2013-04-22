<?php
namespace framework\mvc;

use framework\exceptions\StrictObject;
use framework\exceptions\TypeException;
use framework\lang\IClassInitialization;

class Assets extends StrictObject implements IClassInitialization {

    /**
     * @var AssetItem[]
     */
    protected $assets = array();

    public function __construct(array $deps){
        $this->addDeps($deps);
    }

    /**
     * @return AssetItem[]
     */
    public function all(){
        return $this->assets;
    }

    /** @return string */
    public function getHtmlInclude(){
        $html = '';
        foreach($this->assets as $asset){
            $render = $asset->getHtmlInclude();
            if ($render){
                $html .= '<!-- ' . $asset->name . ' -->' . "\n";
                $html .= $render . "\n";
            }
        }

        return $html;
    }

    /**
     * @param array $deps
     */
    public function addDeps(array $deps){
        foreach($deps as $name => $dep){

            // TODO: conflicts works?
            if ($this->assets[ $name ])
                continue;

            $asset = new AssetItem($name, $dep);
            if ($asset->isExists()){
                $eDeps = $asset->getDeps();
                if ($eDeps && sizeof($eDeps)){
                    $this->getAllAssets($eDeps);
                }
            }
            $this->assets[ $name ] = $asset;
        }
    }


    public static function initialize(){
        AssetItem::registerHtmlAssetExt('js', function($file){
            return '<script type="text/javascript" src="' . $file . '"></script>';
        });
        AssetItem::registerHtmlAssetExt('css', function($file){
            return '<link rel="stylesheet" type="text/css" href="' . $file . '">';
        });
    }
}

class AssetItem {

    public $name;
    public $version;
    public $patternVersion;
    public $meta;

    private $dep;

    public function __construct($name, $dep){
        $this->name = $name;
        $this->dep  = $dep;

        $version = $dep['version'];
        $this->patternVersion = $version;

        $findVersion = false;
        $dirs = glob(ROOT . 'assets/' . $name . '~*', GLOB_ONLYDIR | GLOB_NOSORT);
        foreach($dirs as $dir){
            $asset = basename($dir);
            $curVer = explode('~', $asset, 2);
            $curVer = $curVer[1];

            if (self::versionPattern($version, $curVer)){
                if (!$findVersion)
                    $findVersion = $curVer;
                else {
                    if (version_compare($curVer, $findVersion, '>')){
                        $findVersion = $curVer;
                    }
                }
            }
        }

        $this->version = $findVersion;
        $this->loadMeta();
    }

    protected function loadMeta(){
        if ($this->version){
            $file = ROOT . 'assets/' . $this->name . '~' . $this->version . '/meta.json';
            if (is_file($file)){
                $this->meta = json_decode(file_get_contents($file), true);
            }
        }
    }

    /**
     * @return array
     */
    public function getDeps(){
        return $this->meta['deps'];
    }

    /**
     * @return array
     */
    public function getFiles(){
        return $this->meta['files'];
    }

    /**
     * @return string
     */
    public function getHtmlInclude(){
        $html = '';
        foreach($this->getFiles() as $file){
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $render = self::$htmlAssetExts[ $ext ];
            if ($render){
                $html .= call_user_func($render, '/assets/' . $this->name . '~' . $this->version . '/' .  $file);
                $html .= "\n";
            }
        }

        return $html;
    }

    /**
     * @return bool
     */
    public function isExists(){
        return !!$this->version && $this->meta;
    }

    private static function versionPattern($pattern, $version){
        return preg_match('#^'. $pattern .'$#', $version);
    }

    private static $htmlAssetExts = array();

    /**
     * @param string $ext
     * @param callable $callback
     * @throws \framework\exceptions\TypeException
     */
    public static function registerHtmlAssetExt($ext, $callback){
        if (IS_DEV && !is_callable($callback)){
            throw new TypeException('callback', 'callable');
        }

        self::$htmlAssetExts[$ext] = $callback;
    }
}