<?php
namespace models;

use framework\lang\String;
use models\mixins\TDefaultInformation;
use models\mixins\TTimeInformation;
use modules\mongodb\ActiveRecord;

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
        foreach($this->groups as $group){
            if ($code == $group->code)
                return true;
        }
        return false;
    }
}