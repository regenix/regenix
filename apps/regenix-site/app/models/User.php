<?php
namespace models;

use regenix\lang\String;
use models\mixins\TDefaultInformation;
use models\mixins\TTimeInformation;
use modules\mongodb\ActiveRecord;
use regenix\validation\Validator;

/**
 * Class User
 * @collection users
 * @package models
 * @indexed login = asc, email = asc, $background, $sparse
 */
class User extends ActiveRecord {

    use TDefaultInformation;
    use TTimeInformation;

    /**
     * @indexed sort=asc, $unique, $background
     * @var string
     */
    public $login;

    /** @var string */
    public $email;

    /**
     * @column pwd
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $salt;

    /**
     * @ref $lazy, $small
     * @var Group[]
     */
    public $groups;

    /**
     * @return $this
     */
    public function register(){
        if (!$this->salt)
            $this->salt = String::randomRandom(4,8);

        $this->password = hash('sha256', $this->password . $this->salt);
        return $this->save();
    }

    /**
     * @param string $code
     * @return bool
     */
    public function isGroup($code){
        $groups = $this->getRawValue('groups');
        foreach((array)$groups as $group){
            if ($code == $group)
                return true;
        }
        return false;
    }
}

class UserValidator extends Validator {

    protected function registration(){
        if ($this->requires('login')->message('Введите логин')->isOk()){
            $this->minLength('login', 3)->message('Минимальная длина логина 3 символа');
        }

        $this->requires('email', 'Введите email')->message('Введите email адрес');
    }
}