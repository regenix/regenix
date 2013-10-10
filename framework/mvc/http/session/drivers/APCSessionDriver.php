<?php
namespace regenix\mvc\http\session\drivers;

use regenix\lang\CoreException;
use regenix\mvc\http\session\SessionDriver;

class APCSessionDriver extends SessionDriver {

    protected $_prefix;
    protected $_ttl;
    protected $_lockTimeout = 10; // if empty, no session locking, otherwise seconds to lock timeout

    protected function getTtl(){
        if ($this->_ttl)
            return $this->_ttl;

        $def = session_get_cookie_params();
        return $this->_ttl = $def['lifetime'];
    }

    public function open($savePath, $sessionName){
        $this->_prefix = '$APCSess.'.$sessionName;
        if (!apc_exists($this->_prefix)) {
            // creating non-empty array @see http://us.php.net/manual/en/function.apc-store.php#107359
            apc_store($this->_prefix.'/TS', array(''));
            apc_store($this->_prefix.'/LOCK', array(''));
        }
        return true;
    }

    public function close(){
        return true;
    }

    public function read($id){
        $key = $this->_prefix.'/'.$id;
        if (!apc_exists($key)) {
            return ''; // no session
        }

        // redundant check for ttl before read
        if ($this->getTtl()) {
            $ts = apc_fetch($this->_prefix.'/TS');
            if (empty($ts[$id])) {
                return ''; // no session
            } elseif (!empty($ts[$id]) && $ts[$id] + $this->getTtl() < time()) {
                unset($ts[$id]);
                apc_delete($key);
                apc_store($this->_prefix.'/TS', $ts);
                return ''; // session expired
            }
        }

        if (!$this->_lockTimeout) {
            $locks = apc_fetch($this->_prefix.'/LOCK');
            if (!empty($locks[$id])) {
                while (!empty($locks[$id]) && $locks[$id] + $this->_lockTimeout >= time()) {
                    usleep(10000); // sleep 10ms
                    $locks = apc_fetch($this->_prefix.'/LOCK');
                }
            }
            /*
            // by default will overwrite session after lock expired to allow smooth site function
            // alternative handling is to abort current process
            if (!empty($locks[$id])) {
                return false; // abort read of waiting for lock timed out
            }
            */
            $locks[$id] = time(); // set session lock
            apc_store($this->_prefix.'/LOCK', $locks);
        }

        return apc_fetch($key); // if no data returns empty string per doc
    }

    public function write($id, $value){
        $ts = apc_fetch($this->_prefix.'/TS');
        $ts[$id] = time();
        apc_store($this->_prefix.'/TS', $ts);

        $locks = apc_fetch($this->_prefix.'/LOCK');
        unset($locks[$id]);
        apc_store($this->_prefix.'/LOCK', $locks);

        return apc_store($this->_prefix.'/'.$id, $value, $this->getTtl());
    }

    public function destroy($id){
        $ts = apc_fetch($this->_prefix.'/TS');
        unset($ts[$id]);
        apc_store($this->_prefix.'/TS', $ts);

        $locks = apc_fetch($this->_prefix.'/LOCK');
        unset($locks[$id]);
        apc_store($this->_prefix.'/LOCK', $locks);

        return apc_delete($this->_prefix.'/'.$id);
    }

    public function gc($lifetime){
        if ($this->getTtl()) {
            $lifetime = min($lifetime, $this->getTtl());
        }
        $ts = apc_fetch($this->_prefix.'/TS');
        foreach ($ts as $id=>$time) {
            if ($time + $lifetime < time()) {
                apc_delete($this->_prefix.'/'.$id);
                unset($ts[$id]);
            }
        }
        return apc_store($this->_prefix.'/TS', $ts);
    }

    public function register(){
        if (APC_ENABLED && SYSTEM_IN_MEM_CACHED){
            parent::register();
        } else
            throw new CoreException('Cannot register APC Session Provider, apc is not installed or enabled');
    }
}