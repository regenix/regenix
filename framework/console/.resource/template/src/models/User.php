<?php
namespace models;

use regenix\mvc\Model;

class User extends Model {
    /** @var string */
    public $email;

    /** @var string */
    public $login;

    /** @var string */
    public $password;

    /** @var string */
    public $salt;
}