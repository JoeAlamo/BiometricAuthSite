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

    /**
     * @param $user_id
     * @return bool
     */
    public function isBiometricallyAuthenticated($user_id)
    {
        $stmt = $this->database->prepare('
            SELECT COUNT(biometric_authenticated_session_id)
            FROM biometric_authenticated_session AS bas
            INNER JOIN biometric_client AS bc ON bc.biometric_client_id = bas.biometric_client_id
            INNER JOIN user AS u ON u.user_id = bc.user_id
            WHERE u.user_id = :user_id
            AND bas.expires > NOW()
        ');
        $stmt->bindParam(':user_id', $user_id, \PDO::PARAM_INT);
        $stmt->execute();

        $count = $stmt->fetchColumn();

        return $count > 0;
    }
}