<?php

namespace framework\io;

use framework\exceptions\CoreException;
use framework\lang\String;
use framework\io\File;

class FileNotOpenException extends CoreException {

    const type = __CLASS__;

    /**
     * @var string
     */
    protected $path;

    public function __construct(File $file){
        $this->path = $file->getPath();
        parent::__construct( String::format('File "%s" is not open to read or write', $file->getPath()) );
    }
}
