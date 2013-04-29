<?php
namespace controllers\api;

use ide\DirectoryType;
use ide\FileType;

class FileApi extends Api {

    public function type(){
        $this->notFoundIfEmpty($this->project);

        $type = FileType::getFileType($this->project, $this->data['file']);
        $this->notFoundIfEmpty($type);

        $result = array();
        $result['type'] = get_class($type);
        $result['icon'] = $type->getIcon();

        $editor = $type->getEditor();
        $result['editor'] = array(
            'assets' =>  $editor->getAssets()
        );

        $this->renderJSON($result);
    }

    public function find(){
        $this->notFoundIfEmpty($this->project);

        $path   = $this->data['path'];
        $files  = $this->project->type->getFiles($path);

        $this->renderJSON($files);
    }

    public function save(){
        $this->notFoundIfEmpty($this->project);

        $path   = $this->data['path'];
        $data   = $this->data['data'];

        $type   = FileType::getFileType($this->project, $path);
        if ($this->data['is_dir']){
            $type = new DirectoryType();
        }

        $result = $type->save($this->project, $path, $data);
        if (!$result)
            throw new \Exception("cannot_save_file", 500);

        $this->renderJSON('ok');
    }

    public function remove(){
        $this->notFoundIfEmpty($this->project);

        $type = FileType::getFileType($this->project, $this->data['path']);
        if ($type->remove($this->project, $this->data['path']))
            $this->renderJSON('ok');
        else
            throw new \Exception("cannot_remove_file", 500);
    }
}