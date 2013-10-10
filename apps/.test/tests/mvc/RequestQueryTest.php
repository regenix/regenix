<?php
namespace tests\mvc;

use regenix\lang\DI;
use regenix\mvc\binding\Binder;
use regenix\mvc\http\Request;
use regenix\mvc\http\RequestQuery;
use tests\RegenixTest;

class RequestQueryTest extends RegenixTest {

    public function testSimple(){
        $request = DI::getInstance(Request::type);
        $binder  = DI::getInstance(Binder::type);

        $query = new RequestQuery('key=value', $request, $binder);
        $this->assertEqual('value', $query->get('key'));

        $query = new RequestQuery('key[]=value1&key[]=value2', $request, $binder);
        $this->assertEqual(array('value1', 'value2'), $query->getArray('key'));

        $query = new RequestQuery('model[name]=name&model[code]=code', $request, $binder);
        $this->assertEqual(array('name' => 'name', 'code' => 'code'), $query->getArray('model'));
    }
}