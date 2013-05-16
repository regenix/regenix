<?php
namespace tests\io;

use framework\io\File;
use tests\BaseTest;

class FileTest extends BaseTest {

    /** @var File */
    protected $file;

    public function onGlobalBefore(){
        $this->file = new File(__DIR__ . '/file.test');
    }

    public function simple(){
        $this->isTrue($this->file->exists());
        $this->eq('test', $this->file->getExtension());
        $this->isTrue($this->file->isFile());

        $parent = $this->file->getParentFile();
        $this->isType(File::type, $parent);
        if ($parent)
            $this->isTrue(is_dir($parent->getPath()));
    }
}