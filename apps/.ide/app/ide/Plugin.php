<?php
namespace ide;

abstract class Plugin {

    /** @var ProjectType[] */
    protected $projectTypes;

    /** @var FileType[] */
    protected $fileTypes;

    /** @return string */
    abstract public function getName();

    /**
     * @return string
     */
    public function getCode(){
        $tmp = explode('\\', get_called_class(), 3);
        return $tmp[1];
    }

    /**
     * @return array
     */
    protected function getAssets(){
        return array();
    }

    /**
     * @return array
     */
    public function getRealAssets(){
        return $this->getAssetRealPaths($this->getAssets());
    }

    /**
     * @param $asset
     * @return string
     */
    public function getAssetRealPath($asset){
        return 'plugins/' . $this->getCode() . '/' . $asset;
    }

    /**
     * @param array $assets
     * @return array
     */
    public function getAssetRealPaths(array $assets){
        $result = array();
        foreach($assets as $asset){
            $result[] = $this->getAssetRealPath($asset);
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getAllRealAssets(){
        $assets = $this->getRealAssets();
        foreach($this->projectTypes as $type){
            $assets = $assets + $type->getRealAssets();
        }
        return $assets;
    }

    /**
     * @param FileType $type
     */
    protected function registerFileType(FileType $type){
        FileType::register($type);
        $this->fileTypes[] = $type;
    }

    /**
     * @param ProjectType $type
     */
    protected function registerProjectType(ProjectType $type){
        ProjectType::register($type);
        $this->projectTypes[] = $type;
    }

    private static $plugins = array();

    /**
     * @param Plugin $plugin
     */
    public static function register(Plugin $plugin){
        self::$plugins[$plugin->getCode()] = $plugin;
    }

    /**
     * @return Plugin[]
     */
    public static function getAll(){
        return self::$plugins;
    }

    /**
     * @param string $code
     * @return Plugin|null
     */
    public static function getByCode($code){
        return self::$plugins[$code];
    }

    /**
     * @param $instance
     * @return Plugin|null
     */
    public static function getByInstance($instance){
        if (is_object($instance)){
            $tmp  = explode('\\', get_class($instance), 3);
            $code = $tmp[1];
            return self::getByCode($code);
        }
        return null;
    }
}