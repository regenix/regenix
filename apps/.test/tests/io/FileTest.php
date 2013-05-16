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
        $this->assert($this->file->exists());
        $this->eq('test', $this->file->getExtension());
        $this->assert($this->file->isFile());

        $parent = $this->file->getParentFile();
        $this->isType(File::type, $parent);
        if ($parent)
            $this->assert(is_dir($parent->getPath()));
    }

    public function read(){
        $this->file->open('r+');
        $this->file->gets();
        $this->file->gets();
        $this->file->gets();

        // test content
        $str =  $this->file->gets();
        $this->assert($str === 'test content', 'Test .gets() method');
        $this->assert($this->file->close(), 'Close file');
    }

    public function write(){
        $file = new File(__FILE__ . '/file2.test');
        $this->assert($file->open('w+'), 'Open for read');

        $file->write('test content');
    }
}