<?php
namespace models;

use framework\lang\String;
use framework\mvc\IHandleBeforeSave;
use modules\mongodb\ActiveRecord;

/**
 * Class User
 * @collection users
 * @package models
 */
class User extends ActiveRecord implements IHandleBeforeSave {

    /**
     * @id
     * @var \MongoId
     */
    public $id;

    /**
     * @column act
     * @var boolean
     */
    public $active = true;

    /**
     * @column crd
     * @var \DateTime
     */
    public $created;

    /**
     * @column upd
     * @timestamp
     * @var \DateTime
     */
    public $updated;

    /** @var string */
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

    public function onBeforeSave($isNew){
        if ($isNew)
            $this->created = new \DateTime();
    }

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