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

    public function add($session_id, $client_random, $ip_address)
    {
        $stmt = $this->database->prepare('
            INSERT INTO biometric_session
            (session_id, client_random, ip_address) values (:session_id, :client_random, :ip_address)
        ');
        $stmt->bindParam(':session_id', $session_id);
        $stmt->bindParam(':client_random', $client_random);
        $stmt->bindParam(':ip_address', $ip_address);

        $success = $stmt->execute();

        if ($success) {
            $biometricSession = new BiometricSession($session_id, $client_random, $ip_address);
            $biometricSession->biometric_session_id = $this->database->lastInsertId();

            return $biometricSession;
        }

        return false;
    }
}