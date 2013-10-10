<?php
namespace regenix\mvc\http\session\drivers;

use regenix\lang\String;
use regenix\mvc\http\session\SessionDriver;

/**
 * Mock class
 * Class ArraySession
 * @package regenix\mvc\http\session
 */
class ArraySessionDriver extends SessionDriver {

    private static $data;
    private static $id;

    public function open($savePath, $sessionName) {
        self::$data = array();
    }

    public function close() {
        self::$data = array();
    }

    public function read($id) {
        return self::$data[$id];
    }

    public function write($id, $value) {
        return self::$data[$id] = $value;
    }

    public function destroy($id) {
        self::$data = array();
    }

    public function gc($lifetime) {
        // nop...
    }

    public function getSessionId(){
        if (self::$id)
            return self::$id;

        session_id( self::$id = String::random(40) );
        return null;
    }
}