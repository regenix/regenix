<?php

namespace regenix\mvc\providers;

use regenix\lang\File;

class FileResponse {

    const type = __CLASS__;

    public $attach;

    /**
     * @var File
     */
    public $file;

    public function __construct($file, $attach = true){
        if(!($file instanceof File))
            $file = new File($file);

        $this->file   = $file;
        $this->attach = $attach;
    }
}
