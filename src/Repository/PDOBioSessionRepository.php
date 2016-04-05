<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 18:17
 */

namespace BiometricSite\Repository;


use BiometricSite\Model\BiometricSession;

class PDOBioSessionRepository implements BioSessionRepositoryInterface {
    private $database;

    public function __construct(\PDO $database) {
        $this->database = $database;
    }

    public function add($session_id, $client_random, $ip_address, $timestamp)
    {
        $stmt = $this->database->prepare('
            INSERT INTO biometric_session
            (session_id, client_random, ip_address, timestamp) values (:session_id, :client_random, :ip_address, :timestamp)
        ');
        $stmt->bindParam(':session_id', $session_id);
        $stmt->bindParam(':client_random', $client_random);
        $stmt->bindParam(':ip_address', $ip_address);
        $stmt->bindParam(':timestamp', $timestamp);

        $success = $stmt->execute();

        if ($success) {
            $biometricSession = $this->findBySessionId($session_id);

            return $biometricSession;
        }

        return false;
    }

    /**
     * @param $biometric_session_id
     * @param $biometric_client_id
     * @return bool
     */
    public function associateSessionToClient($biometric_session_id, $biometric_client_id)
    {
        $stmt = $this->database->prepare('
            UPDATE biometric_session SET biometric_client_id = :biometric_client_id
            WHERE biometric_session_id = :biometric_session_id
        ');
        $stmt->bindParam('biometric_client_id', $biometric_client_id);
        $stmt->bindParam('biometric_session_id', $biometric_session_id);

        return $stmt->execute();
    }

    /**
     * @param $session_id
     * @return \BiometricSite\Model\BiometricSession|false
     */
    public function findBySessionId($session_id)
    {
        $stmt = $this->database->prepare('
            SELECT * FROM biometric_session
            WHERE session_id = :session_id
        ');
        $stmt->bindParam(':session_id', $session_id);

        $stmt->execute();

        $stmt->setFetchMode(\PDO::FETCH_CLASS, 'BiometricSite\\Model\\BiometricSession');

        return $stmt->fetch();
    }

    public function update($biometric_session_id, $client_random, $ip_address, $timestamp)
    {
        $stmt = $this->database->prepare('
            UPDATE biometric_session
            SET client_random = :client_random, ip_address = :ip_address, timestamp = :timestamp
            WHERE biometric_session_id = :biometric_session_id
        ');
        $stmt->bindParam(':client_random', $client_random);
        $stmt->bindParam(':ip_address', $ip_address);
        $stmt->bindParam(':timestamp', $timestamp);
        $stmt->bindParam(':biometric_session_id', $biometric_session_id);

        return $stmt->execute();
    }
}