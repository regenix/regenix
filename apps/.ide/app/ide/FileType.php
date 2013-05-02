<?php
namespace ide;

use framework\lang\IClassInitialization;
use ide\editors\DirectoryEditor;
use ide\editors\SourceEditor;

abstract class FileType {

    /** @return string */
    abstract protected function getIcon();

    /**
     * @return string
     */
    public function getRealIcon(){
        $icon = $this->getIcon();
        $plugin = $this->getPlugin();
        if ($plugin)
            return $plugin->getAssetRealPath($icon);
        else
            return $icon;
    }

    /** @return EditorType */
    abstract public function getEditor();

    /**
     * @param Project $project
     * @param $file
     * @param $data
     * @return mixed
     */
    abstract public function save(Project $project, $file, $data);

    /**
     * @param Project $project
     * @param $file
     * @return mixed
     */
    abstract public function remove(Project $project, $file);

    /**
     * @return Plugin
     */
    public function getPlugin(){
        return Plugin::getByInstance($this);
    }

    /**
     * @return array
     */
    public function toJson(){
        return array(
            'type' => get_class($this),
            'icon' => $this->getIcon(),
            'editor' => $this->getEditor()->toJson()
        );
    }

    /**
     * @param $project
     * @param $file
     * @return bool
     */
    abstract public function isMatch(Project $project, $file);

    /** @var FileType[] */
    public static $types = array();

    public static function register(FileType $type, $prepend = true){
        if ($prepend)
            array_unshift(self::$types, $type);
        else
            self::$types[] = $type;
    }

    public static function getFileType(Project $project, $file){
        foreach(self::$types as $type){
            if ($type->isMatch($project, $file))
                return $type;
        }
    }
}