<?php
namespace tests;

use regenix\Regenix;
use regenix\Application;
use regenix\lang\ClassFileScanner;
use regenix\lang\ClassLoader;
use regenix\lang\ClassScanner;
use regenix\lang\File;
use regenix\lang\String;

class ClassloaderTest extends RegenixTest {

    const type = __CLASS__;

    public function project(){
        $this->assertRequire(ClassScanner::find('\\Bootstrap'));
        $this->assertRequire(ClassScanner::find('Bootstrap'));
        $this->assertRequire(ClassScanner::find('controllers\\Application'));
        $this->assertNotRequire(ClassScanner::find('controllers\\Application.php'));
    }

    public function testAddPath(){
        $testFile = new File(
            Regenix::getTempPath() . '/' . String::random(5) . '/' . String::random(5) . '.php'
        );
        $testFile->getParentFile()->mkdirs();

        $testClass = 'A' . String::random(12);
        $testFile->open('w+');
        $testFile->write('<?php namespace tests;

            abstract class ' . $testClass . ' { }
            interface interface' . $testClass . ' { }
            class child'.$testClass . ' implements interface'.$testClass . ' { }'
        );
        $testFile->close();

        $scanner = new ClassFileScanner($testFile->getPath());
        $meta    = $scanner->getMeta();
        $this->assertIssetArray(
            $meta,
            array('tests\\' . $testClass, 'tests\\interface' . $testClass, 'tests\\child' . $testClass)
        );

        ClassScanner::addClassPath($testFile->getParent() . '/');

        $this->assertRequire($info = ClassScanner::find('tests\\' . $testClass));
        $this->assert($info && $info->isAbstract());

        $this->assertRequire($info = ClassScanner::find('tests\\interface' . $testClass));
        $this->assert($info && $info->isInterface());
        $this->assert($info->isParentOf('tests\\child' . $testClass));

        $this->assertRequire($info = ClassScanner::find('tests\\child' . $testClass));
        $this->assert($info && $info->isChildOf('tests\\interface' . $testClass));
        $this->assertArraySize(1, $info->getImplements());

        $testFile->delete();
        $testFile->getParentFile()->delete();
    }
}