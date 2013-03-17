<?php

namespace framework\io;

use framework\lang\String;
use framework\io\File;

class FileIOException extends \framework\exceptions\CoreException {

    const type = __CLASS__;

    /**
     * @var string
     */
    protected $path;

    public function __construct(File $file){
       
        $this->path = $file->getPath();
        parent::__construct( String::format('File "%s" can\'t read', $file->getPath()) );
    }
}
