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

    /**
     * @param int $id
     * @return \BiometricSite\Model\User|false
     */
    public function find($id) {
        return $this->findBy('user_id', $id, 1, \PDO::PARAM_INT);
    }

    /**
     * @param string $username
     * @return \BiometricSite\Model\User|false
     */
    public function findByUsername($username)
    {
        return $this->findBy('username', $username, 1);
    }

    /**
     * @param string $column Name of column to restrict find
     * @param mixed $value Value column must be
     * @param int $limit Maximum results to return
     * @param int $valueType The type of $value
     * @return \BiometricSite\Model\User|false
     */
    private function findBy($column, $value, $limit, $valueType = \PDO::PARAM_STR) {
        $stmt = $this->database->prepare("
            SELECT *
            FROM user
            WHERE :column = :value
            LIMIT :limit
        ");
        $stmt->bindParam(':column', $column);
        $stmt->bindParam(':value', $value, $valueType);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $stmt->setFetchMode(\PDO::FETCH_CLASS, 'BiometricSite\\Model\\User');

        return $stmt->fetch();
    }
}