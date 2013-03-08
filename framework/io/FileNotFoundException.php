<?php

namespace framework\io;

use framework\utils\StringUtils;

class FileNotFoundException extends \framework\exceptions\CoreException {

    public $file;

    public function __construct(File $file){
        parent::__construct( StringUtils::format('File "%s" not found', $file->getAbsolutePath()) );
        $this->file = $file;
    }
}
