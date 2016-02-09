<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 09/02/2016
 * Time: 15:54
 */

namespace BiometricSite\Repository;


class PDOUserRepository implements UserRepositoryInterface {
    private $database;

    public function __construct(\PDO $database) {
        $this->database = $database;
    }

    public function find($user_id) {
        $stmt = $this->database->prepare('
            SELECT *
            FROM user
            WHERE user_id = :id
        ');
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();

        $stmt->setFetchMode(\PDO::FETCH_CLASS, 'BiometricSite\\Model\\User');

        return $stmt->fetch();
    }

    public function findByUsername($username)
    {
        $stmt = $this->database->prepare('
            SELECT *
            FROM user
            WHERE username = :username
        ');
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $stmt->setFetchMode(\PDO::FETCH_CLASS, 'BiometricSite\\Model\\User');

        return $stmt->fetch();
    }
}