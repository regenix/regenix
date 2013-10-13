<?php
namespace tests\lang;

use regenix\lang\File;
use regenix\lang\SystemFileCache;
use tests\RegenixTest;

class SystemFileCacheTest extends RegenixTest {

    public function testSimple(){
        $this->assertType('string', SystemFileCache::getId());
        $this->assertRequire(SystemFileCache::getId());

        $this->assert(is_dir(SystemFileCache::getTempDirectory()));

        SystemFileCache::set('foobar', 123);
        $this->assertStrongEqual(123, SystemFileCache::get('foobar'));

        SystemFileCache::remove('foobar');
        $this->assertNull(SystemFileCache::get('foobar'));
    }

    public function testWithId(){
        $defId = SystemFileCache::getId();

        SystemFileCache::setId('foobar');
        $this->assertStrongEqual('foobar', SystemFileCache::getId());

        SystemFileCache::set('xyz', 123);
        $this->assertStrongEqual(123, SystemFileCache::get('xyz'));

        SystemFileCache::setId('');
        $this->assertNull(SystemFileCache::get('xyz'));

        SystemFileCache::setId('foobar');
        $this->assertStrongEqual(123, SystemFileCache::get('xyz'));


        SystemFileCache::setId('');
        $this->assertNot(SystemFileCache::remove('xyz'));

        SystemFileCache::setId('foobar');
        $this->assertStrongEqual(123, SystemFileCache::get('xyz'));
        $this->assert(SystemFileCache::remove('xyz'));

        SystemFileCache::setId($defId);
    }

    public function testInterval(){
        SystemFileCache::set('foobar', 123, 0); // 0 sec
        $this->sleep(0, 100);
        $this->assertNull(SystemFileCache::get('foobar'));
        $this->assertNot(SystemFileCache::remove('foobar'));
    }

    public function testWithFileCheck(){
        $tmpFile = File::createTempFile();

        SystemFileCache::setWithCheckFile('foobar', 123, $tmpFile->getPath());
        $this->assertEqual(123, SystemFileCache::getWithCheckFile('foobar', $tmpFile->getPath()));

        touch($tmpFile->getPath(), time() + 11);
        $this->assertNull(SystemFileCache::getWithCheckFile('foobar', $tmpFile->getPath()));
        $this->assert(SystemFileCache::remove('foobar'));
    }

    public function testSerialization(){
        $arr = array('x' => 10, 'y' => 20);
        SystemFileCache::set('foobar', $arr);
        $this->assertStrongEqual($arr, SystemFileCache::get('foobar'));
        $this->assert(SystemFileCache::remove('foobar'));

        $obj = new SerializedObject();
        $obj->x = 10;
        $obj->y = 20;

        SystemFileCache::set('foobar', $obj);
        $cache = SystemFileCache::get('foobar');
        $this->assertType(SerializedObject::type, $cache);
        if ($this->isLastOk()){
            $this->assertEqual(10, $cache->x);
            $this->assertEqual(20, $cache->y);
        }
        $this->assert(SystemFileCache::remove('foobar'));
    }
}

class SerializedObject {
    const type = __CLASS__;

    public $x;
    public $y;

    public static function __set_state($array) {
        $obj = new static();
        $obj->x = $array['x'];
        $obj->y = $array['y'];
        return $obj;
    }
}