<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 17:33
 */

namespace BiometricSite\Repository;


class PDOPrevClientTimestampRepository implements PrevClientTimestampRepositoryInterface {
    private $database;

    public function __construct(\PDO $database) {
        $this->database = $database;
    }

    /**
     * @param $biometric_client_id
     * @param $timestamp
     * @return bool
     */
    public function addOrUpdate($biometric_client_id, $timestamp) {
        $exists = $this->find($biometric_client_id);

        return $exists === false ? $this->add($biometric_client_id, $timestamp) : $this->update($biometric_client_id, $timestamp);
    }

    /**
     * @param int $biometric_client_id
     * @return int|false
     */
    public function find($biometric_client_id) {
        $stmt = $this->database->prepare('
            SELECT `timestamp`
            FROM previous_client_timestamp
            WHERE biometric_client_id = :biometric_client_id
        ');
        $stmt->bindParam(':biometric_client_id', $biometric_client_id);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    /**
     * @desc Determine whether timestamp is within 10 minutes (-5/+5) of current time
     * @param $timestamp
     * @return bool
     */
    public function isFresh($timestamp) {
        // Is (timestamp) BETWEEN NOW() - 5 minutes and NOW() + 5 minutes?
        $stmt = $this->database->prepare('
            SELECT FROM_UNIXTIME(:timestamp)
            BETWEEN DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            AND DATE_ADD(NOW(), INTERVAL 5 MINUTE)
            AS is_fresh;
        ');
        $stmt->bindParam('timestamp', $timestamp);
        $stmt->execute();

        return (bool)$stmt->fetchColumn();
    }

    /**
     * @param $biometric_client_id
     * @param $timestamp
     * @return bool
     */
    private function add($biometric_client_id, $timestamp) {
        $stmt = $this->database->prepare('
            INSERT INTO previous_client_timestamp
            (timestamp, biometric_client_id) VALUES (:timestamp, :biometric_client_id)
        ');
        $stmt->bindParam(':timestamp', $timestamp);
        $stmt->bindParam(':biometric_client_id', $biometric_client_id);

        return $stmt->execute();
    }

    /**
     * @param $biometric_client_id
     * @param $timestamp
     * @return bool
     */
    private function update($biometric_client_id, $timestamp) {
        $stmt = $this->database->prepare('
            UPDATE previous_client_timestamp
            SET timestamp = :timestamp
            WHERE biometric_client_id = :biometric_client_id
        ');
        $stmt->bindParam('timestamp', $timestamp, \PDO::PARAM_INT);
        $stmt->bindParam('biometric_client_id', $biometric_client_id);

        return $stmt->execute();
    }
}