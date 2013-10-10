<?php
namespace regenix\exceptions;

use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\lang\String;

/**
 * Class FileNotOpenException
 * @package regenix\lang
 */
class FileNotOpenException extends CoreException {

    const type = __CLASS__;

    /**
     * @var string
     */
    protected $path;

    public function __construct(File $file){
        $this->path = $file->getPath();
        parent::__construct( String::format('File "%s" is not opened to read or write', $file->getPath()) );
    }
}
