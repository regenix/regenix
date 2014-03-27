<?php
namespace regenix\mvc\http\session;

use regenix\core\Regenix;
use regenix\lang\DI;
use regenix\lang\Injectable;
use regenix\lang\Singleton;
use regenix\lang\StrictObject;
use regenix\lang\String;

class Flash extends StrictObject
    implements Singleton, Injectable {

    const type = __CLASS__;

    /** @var Session */
    protected $session;

    /**
     * @param Session $session
     */
    public function __construct(Session $session){
        $this->session = $session;
    }

    /**
     * @param null $value
     * @return $this|scalar|null
     */
    public function success($value = null){
        if ( $value === null )
            return $this->get("success");
        else
            return $this->put("success", $value);
    }

    /**
     * @param null $value
     * @return $this|scalar|null
     */
    public function error($value = null){
        if ( $value === null )
            return $this->get("error");
        else
            return $this->put("error", $value);
    }

    /**
     * @param null $value
     * @return $this|scalar|null
     */
    public function warning($value = null){
        if ( $value === null )
            return $this->get("warning");
        else
            return $this->put("warning", $value);
    }

    /**
     * @param null $value
     * @return $this|scalar|null
     */
    public function debug($value = null){
        $app = Regenix::app();
        if ($app && $app->isDev()){
            if ( $value === null )
                return $this->get("debug");
            else
                return $this->put("debug", $value);
        }
    }

    /**
     * @param string $name
     * @param scalar $value
     * @return $this
     */
    public function put($name, $value){
        $this->session->put($name . '$$flash', $value);
        $this->session->put($name . '$$flash_i', 1);
        return $this;
    }

    /**
     * keep flash value
     * @param string $name
     * @param int $inc
     * @return $this
     */
    public function keep($name, $inc = 1){
        $i = $this->session->get($name . '$$flash_i');
        if ( $i !== null ){
            $i = (int)$i + $inc;
            if ( $i < 0 ){
                $this->remove($name);
            } else {
                $this->session->put($name . '$$flash_i', $i);
            }
        }
        return $this;
    }

    public function touch($name){
        return $this->keep($name, -1);
    }

    public function touchAll(){
        $all = $this->session->all();
        foreach($all as $key => $value){
            if ( String::endsWith($key, '$$flash') ){
                $this->touch(substr($key, 0, -7));
            }
        }
        return $this;
    }

    public function get($name, $def = null){
        return $this->session->get($name . '$$flash', $def);
    }

    /**
     * exists flash value
     * @param string $name
     * @return bool
     */
    public function has($name){
        return $this->session->has($name . '$$flash');
    }

    /**
     * hard remove flash value
     * @param $name
     * @return $this
     */
    public function remove($name){
        $this->session->remove($name . '$$flash');
        $this->session->remove($name . '$$flash_i');
        return $this;
    }

    /**
     * @return Flash
     */
    public static function getInstance() {
        return DI::getInstance(__CLASS__);
    }
}