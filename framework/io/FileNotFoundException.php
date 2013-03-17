<?php

namespace framework\io;

use framework\lang\String;

class FileNotFoundException extends \framework\exceptions\CoreException {

    const type = __CLASS__;

    public $file;

    public function __construct(File $file){
        parent::__construct( String::format('File "%s" not found', $file->getAbsolutePath()) );
        $this->file = $file;
    }
}
