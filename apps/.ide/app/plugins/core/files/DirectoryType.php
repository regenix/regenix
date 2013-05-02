<?php
namespace plugins\core\files;

use framework\lang\String;
use ide\EditorType;
use ide\FileType;
use ide\Project;
use plugins\core\editors\DirectoryEditor;

class DirectoryType extends FileType {

    /** @return string */
    protected function getIcon() {
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
        if (String::endsWith($file, '/'))
            return true;

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