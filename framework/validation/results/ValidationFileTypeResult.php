<?php
namespace regenix\validation\results;

use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\mvc\http\UploadFile;

class ValidationFileTypeResult extends ValidationResult {

    private $types;

    public function __construct(array $types){
        $this->types = array_map('strtolower', $types);
    }

    public function check($value){
        $file = $value;
        if (!($file instanceof File))
            throw new CoreException('Value of `FileMaxSize` validator must be an instance of File class');

        $ext = strtolower($file->getExtension());
        if ($file instanceof UploadFile){
            $ext = strtolower($file->getMimeExtension());
        }

        return in_array($ext, $this->types, true);
    }

    public function getMessageAttr(){
        return array('param' => implode(', ', $this->types));
    }
}
