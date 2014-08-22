<?php
namespace dao;

use models\User;
use regenix\mvc\db\GenericDao;

class UserDao extends GenericDao {
    const DATA_TYPE = 'user';

    /**
     * @param $email
     * @return User
     */
    public function findByEmail($email) {
        return $this->findOne('email = ?', [$email]);
    }
}