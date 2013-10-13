<?php
namespace tests\io;

use regenix\core\Regenix;
use regenix\lang\File;
use tests\RegenixTest;

class FileTest extends RegenixTest {

    /** @var File */
    protected $file;

    public function onGlobalBefore(){
        $this->file = new File(__DIR__ . '/file.test');
    }

    public function testName(){
        $file = new File('path/to/file.ext');

        $this->assertEqual('path/to/file.ext', $file->getPath());
        $this->assertEqual('ext', $file->getExtension());
        $this->assertEqual('file.ext', $file->getName());
        $this->assertEqual('file', $file->getNameWithoutExtension());

        $this->assert($file->hasExtension('ext'));
        $this->assert($file->hasExtension('EXT'));
        $this->assert($file->hasExtension('.ext'));
        $this->assert($file->hasExtensions(array('ext', 'jpg')));

        $this->assertEqual('path/to', $file->getParent());
        $this->assertType(File::type, $parent = $file->getParentFile());
        if ($this->isLastOk()){
            $this->assertEqual('path/to', $parent->getPath());
        }

        $this->assertEqual('/file.ext', $file->getRelativePath(new File('path/to')));
        $this->assertEqual('/file.ext', $file->getRelativePath(new File('path\\to')));

        $file = new File('file.ExT');
        $this->assertEqual('ext', $file->getExtension());
    }

    public function testCreateFromFile(){
        $path = new File('path/to');
        $file = new File('file.ext', $path);

        $this->assertEqual('path/to/file.ext', $file->getPath());
    }

    public function testSimple(){
        $this->assert($this->file->exists(), 'Check file exists');
        $this->assertEqual('test', $this->file->getExtension(), 'Check file extension');
        $this->assert($this->file->isFile(), 'Is file type check');

        $parent = $this->file->getParentFile();
        $this->assertType(File::type, $parent, 'Check class of get parent');
        if ($parent)
            $this->assert(is_dir($parent->getPath()), 'Check parent of file for type as dir');
    }

    public function testRead(){
        $this->file->open('r+');
        $this->assert($this->file->isOpened());

        $this->file->gets();
        $this->file->gets();
        $this->file->gets();

        // test content
        $str =  $this->file->gets();
        $this->assert($str === 'test content', 'Test .gets() method');
        $this->assert($this->file->close(), 'Close file');
    }

    public function testCreateTemp(){
        $file = File::createTempFile();
        $this->assertType(File::type, $file);
        if ($this->isLastOk()){
            $this->assert($file->isFile());
        }
        $this->assert($file->delete());
    }

    public function testWriteAndDelete(){
        $file = File::createTempFile('.test');
        $this->assertRequire($file->open('w+'), 'Open for write');

        $this->assert($file->write('test content') === 12, 'Write string to file');
        $this->assert($file->write('test content', 5) === 5, 'Write limit string to file');

        $file->seek(0);
        $this->assert($file->read() === 'test contenttest ', 'Read written string from file');

        $this->assert($file->close(), 'Close file');
        $this->assert($file->delete(), 'Check closed file');
    }

    public function testGetPutContents(){
        $tmp = '
# TEST FILE

test content';
        $contents = $this->file->getContents();
        $this->assertEqual($tmp, $contents);
        $this->assertNot($this->file->isOpened());

        $file = File::createTempFile('.test');
        $file->putContents('foobar');
        $this->assertNot($file->isOpened());

        $contents = $file->getContents();
        $this->assertEqual('foobar', $contents);
        $this->assert($file->delete());
    }

    public function testDirCreatingAndDeleting(){
        $tempDir = new File(Regenix::getTempPath() . '.foo/bar');
        $this->assertNot($tempDir->mkdir() && !$tempDir->isDirectory());
        $this->assert($tempDir->mkdirs());
        $this->assert($tempDir->isDirectory());

        $file = new File('file.ext', $tempDir);
        $file->putContents('foobar');

        $this->assert($tempDir->delete());
        $this->assertNot($file->exists());
        $this->assertNot($tempDir->exists());

        $this->assert($tempDir->getParentFile()->delete());
        $this->assertNot($tempDir->getParentFile()->exists());
    }

    public function testRenameTo(){
        $file = new File(Regenix::getTempPath() . 'foobar.ext');
        $this->assertNot($file->renameTo(new File('foobar2.ext', $file->getParentFile())));

        $file->putContents('');
        $this->assert($file->exists());
        $this->assert($file->renameTo($newFile = new File('foobar2.ext', $file->getParentFile())));
        $this->assertNot($file->exists());

        $this->assert($newFile->delete());

        $dir = new File(Regenix::getTempPath() . 'foobar');
        $this->assertNot($file->renameTo(new File('foobar2', $file->getParentFile())));
        $this->assert($dir->mkdirs());

        $this->assert($dir->renameTo($newDir = new File('foobar2', $file->getParentFile())));
        $this->assertNot($dir->isDirectory());
        $this->assert($newDir->isDirectory());
        $this->assert($newDir->delete());
    }
}