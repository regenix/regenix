<?php
namespace ide;

use framework\lang\IClassInitialization;
use ide\editors\DirectoryEditor;
use ide\editors\SourceEditor;

abstract class FileType {

    /** @return string */
    abstract public function getIcon();

    /** @return EditorType */
    abstract public function getEditor();

    /** @return bool */
    abstract public function save(Project $project, $file, $data);

    /**
     * @param Project $project
     * @param $file
     * @return mixed
     */
    abstract public function remove(Project $project, $file);

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

class TextType extends FileType {

    /** @return string */
    public function getIcon() {
        return 'img/icons/filetypes/text.png';
    }

    /** @return EditorType */
    public function getEditor() {
        return new SourceEditor();
    }

    public function isMatch(Project $project, $file){
        return true;
    }

    public function save(Project $project, $file, $data){
        return (bool)file_put_contents($project->getPath() . $file, $data);
    }

    /**
     * @param Project $project
     * @param $file
     * @return mixed
     */
    public function remove(Project $project, $file) {
        if (file_exists($file = $project->getPath() . $file)){
            return @unlink($file);
        }
        return false;
    }
}

class DirectoryType extends FileType {

    /** @return string */
    public function getIcon() {
        return 'img/icons/filetypes/folder.png';
    }

    /** @return EditorType */
    public function getEditor() {
        return new DirectoryEditor();
    }

    /**
     * @param $project
     * @param $file
     * @return bool
     */
    public function isMatch(Project $project, $file) {
        return is_dir($project->getPath() . $file);
    }

    /**
     * @param Project $project
     * @param $file
     * @param $data
     * @return bool
     */
    public function save(Project $project, $file, $data) {
        $dir = $project->getPath() . $file;
        if (!is_dir($dir) && file_exists($dir))
            return false;

        if (!is_dir($dir)){
            return mkdir($dir, 0777, true);
        }
        return true;
    }

    private static function recursiveDelete($str){
        if(is_file($str)){
            return @unlink($str);
        }
        elseif(is_dir($str)){
            $scan = glob(rtrim($str,'/').'/*');
            foreach($scan as $index=>$path){
                self::recursiveDelete($path);
            }
            return @rmdir($str);
        }
    }

    /**
     * @param Project $project
     * @param $file
     * @return mixed
     */
    public function remove(Project $project, $file) {
        if (!is_readable($dir = $project->getPath() . $file)) return false;
        if (!is_dir($dir)) return false;

        return self::recursiveDelete($dir);
    }
}