<?php
namespace regenix\exceptions;

use regenix\lang\File;
use regenix\lang\String;

/**
 * Class FileIOException
 * @package regenix\lang
 */
class FileIOException extends IOException {

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
