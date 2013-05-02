<?php
namespace controllers\api;

use ide\DirectoryType;
use ide\FileType;

class FileApi extends Api {

    public function type($file = null){
        $this->notFoundIfEmpty($this->project, $file);

        $type = FileType::getFileType($this->project, $file);
        $this->notFoundIfEmpty($type);

        $result = array();
        $result['type'] = get_class($type);
        $result['icon'] = $type->getIcon();

        $editor = $type->getEditor();
        $result['editor'] = array(
            'assets' =>  $editor->getAssets()
        );
        return $result;
    }

    public function find($path = ''){
        $this->notFoundIfEmpty($this->project, $path);
        $files = $this->project->type->getFiles($path);
        return $files;
    }

    public function save($file = '', $data = null){
        $this->notFoundIfEmpty($this->project, $file);

        $type   = FileType::getFileType($this->project, $file);
        $result = $type->save($this->project, $file, $data);
        if (!$result)
            throw new \Exception("cannot_save_file");
    }

    public function remove($file = ''){
        $this->notFoundIfEmpty($this->project, $file);

        $type = FileType::getFileType($this->project, $file);
        if (!$type->remove($this->project, $file))
            throw new \Exception("cannot_remove_file");
    }
}