<?php
namespace regenix\validation\results;

use regenix\lang\CoreException;
use regenix\lang\File;

class ValidationFileMaxSizeResult extends ValidationResult {

    private $size;

    public function __construct($size){
        $this->size = $size;
    }

    public function check($value){
        $file = $value;
        if (!($file instanceof File))
            throw new CoreException('Value of `FileMaxSize` validator must be an instance of File class');

        return $file->length() <= $this->size;
    }

    public function getMessageAttr(){
        return array('param' => $this->size);
    }
}
