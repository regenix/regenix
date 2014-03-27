<?php
namespace regenix\template;

use regenix\lang\String;

class RegenixTemplateVariable {
    protected $var;
    /** @var RegenixTemplate */
    protected $ctx;

    /** @var RegenixTemplateVariable */
    protected static $instance;
    protected static $modifiers = array();

    protected function __construct($var, RegenixTemplate $ctx){
        $this->var = $var;
        $this->ctx = $ctx;
    }

    public function raw(){
        return $this;
    }

    public function format($format){
        if ($this->var > 2147483647)
            $this->var = (int)($this->var / 1000);

        $this->var = date($format, $this->var);
        return $this;
    }

    public function lowerCase(){
        $this->var = strtolower($this->var);
        return $this;
    }

    public function upperCase(){
        $this->var = strtoupper($this->var);
        return $this;
    }

    public function trim(){
        $this->var = trim($this->var);
        return $this;
    }

    public function substring($from, $to = null){
        $this->var = String::substring($this->var, $from, $to);
        return $this;
    }

    public function replace($what, $replace){
        $this->var = str_replace($what, $replace, $this->var);
        return $this;
    }

    public function nl2br(){
        $this->var = nl2br($this->var);
        return $this;
    }

    public static function current($var, RegenixTemplate $ctx){
        if (self::$instance){
            self::$instance->ctx = $ctx;
            self::$instance->var = $var;
            return self::$instance;
        }
        return self::$instance = new RegenixTemplateVariable($var, $ctx);
    }

    public function __toString(){
        return (string)$this->var;
    }

    public function __call($name, $args){
        return $this->var = $this->ctx->callFilter($name, $this->var, $args);
    }
}