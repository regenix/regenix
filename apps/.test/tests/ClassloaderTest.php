<?php
namespace tests;

use framework\Core;
use framework\Project;
use framework\lang\ClassLoader;
use framework\lang\ClassScanner;

class ClassloaderTest extends RegenixTest {

    const type = __CLASS__;

    public function project(){
        $this->assertRequire(ClassScanner::find('\\Bootstrap'));
        $this->assertRequire(ClassScanner::find('Bootstrap'));
        $this->assertRequire(ClassScanner::find('controllers\\Application'));
        $this->assertNotRequire(ClassScanner::find('controllers\\Application.php'));
    }
}