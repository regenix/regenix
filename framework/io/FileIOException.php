<?php

namespace framework\io;

use framework\utils\StringUtils;
use framework\io\File;

class FileIOException extends \framework\exceptions\CoreException {

    /**
     * @var string
     */
    protected $path;

    public function __construct(File $file){
       
        $this->path = $file->getPath();
        parent::__construct( StringUtils::format('File "%s" can\'t read', $file->getPath()) );
    }
}
