<?php
namespace tests\io;

use framework\io\File;
use tests\RegenixTest;

class FileTest extends RegenixTest {

    /** @var File */
    protected $file;

    public function onGlobalBefore(){
        $this->file = new File(__DIR__ . '/file.test');
    }

    public function simple(){
        $this->assert($this->file->exists(), 'Check file exists');
        $this->assertEqual('test', $this->file->getExtension(), 'Check file extension');
        $this->assert($this->file->isFile(), 'Is file type check');

        $parent = $this->file->getParentFile();
        $this->assertType(File::type, $parent, 'Check class of get parent');
        if ($parent)
            $this->assert(is_dir($parent->getPath()), 'Check parent of file for type as dir');
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

    public function writeAndDelete(){
        $file = File::createTempFile('.test');
        $this->assertRequire($file->open('w+'), 'Open for write');

        $this->assert($file->write('test content') === 12, 'Write string to file');
        $this->assert($file->write('test content', 5) === 5, 'Write limit string to file');

        $file->seek(0);
        $this->assert($file->read() === 'test contenttest ', 'Read written string from file');

        $this->assert($file->close(), 'Close file');
        $this->assert($file->delete(), 'Check closed file');
    }
}