<?php
namespace regenix\mvc\http\session\drivers;

use regenix\core\Regenix;
use regenix\lang\DI;
use regenix\mvc\http\session\SessionDriver;

class DefaultSessionDriver extends SessionDriver {

    public function open($savePath, $sessionName) {
        // nop
    }

    public function close() {
        // nop
    }

    public function read($id) {
        // nop
    }

    public function write($id, $value) {
        // nop
    }

    public function destroy($id) {
        // nop
    }

    public function gc($lifetime) {
        // nop
    }

    public function register(){
        DI::bind($this, SessionDriver::type);
    }
}
