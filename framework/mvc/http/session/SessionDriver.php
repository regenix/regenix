<?php
namespace regenix\mvc\http\session;

use regenix\core\Regenix;
use regenix\lang\DI;
use regenix\lang\Singleton;

abstract class SessionDriver implements Singleton {

    const type = __CLASS__;

    abstract public function open($savePath, $sessionName);
    abstract public function close();
    abstract public function read($id);
    abstract public function write($id, $value);
    abstract public function destroy($id);
    abstract public function gc($lifetime);

    public function getSessionId(){
        return session_id();
    }

    public function register(){
        session_set_save_handler(
            array($this, 'open'), array($this, 'close'),
            array($this, 'read'), array($this, 'write'),
            array($this, 'destroy'), array($this, 'gc')
        );

        DI::bind($this, SessionDriver::type);
    }
}