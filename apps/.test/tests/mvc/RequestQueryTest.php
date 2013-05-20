<?php
namespace tests\mvc;

use framework\mvc\RequestQuery;
use tests\RegenixTest;

class RequestQueryTest extends RegenixTest {

    public function testSimple(){
        $request = new RequestQuery('key=value');
        $this->assertEqual('value', $request->get('key'));

        $query = new RequestQuery('key[]=value1&key[]=value2');
        $this->assertEqual(array('value1', 'value2'), $query->getArray('key'));

        $query = new RequestQuery('model[name]=name&model[code]=code');
        $this->assertEqual(array('name' => 'name', 'code' => 'code'), $query->getArray('model'));
    }
}