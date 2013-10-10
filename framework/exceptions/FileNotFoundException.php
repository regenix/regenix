<?php
namespace regenix\exceptions;

use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\lang\String;

/**
 * Class FileNotFoundException
 * @package regenix\lang
 */
class FileNotFoundException extends CoreException {

    const type = __CLASS__;

    public $file;

    public function __construct(File $file){
        parent::__construct( String::format('File "%s" not found', $file->getPath()) );
        $this->file = $file;
    }
}
