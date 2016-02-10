<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 19:01
 */

namespace BiometricSite\Repository;


use BiometricSite\Model\BiometricAuthenticatedSession;

class PDOBioAuthSessionRepository implements BioAuthSessionRepositoryInterface {
    private $database;

    public function __construct(\PDO $database) {
        $this->database = $database;
    }

    public function add($biometric_client_id, $biometric_session_id, $duration = 30)
    {
        $stmt = $this->database->prepare('
            INSERT INTO biometric_authenticated_session
            (biometric_client_id, biometric_session_id, expires) values
            (:biometric_client_id, :biometric_session_id, DATE_ADD(NOW(), INTERVAL :duration SECOND))
        ');
        $stmt->bindParam(':biometric_client_id', $biometric_client_id);
        $stmt->bindParam(':biometric_session_id', $biometric_session_id);
        $stmt->bindParam(':duration', $duration);

        $success = $stmt->execute();

        if ($success) {
            $biometricAuthSession = new BiometricAuthenticatedSession($duration, $biometric_client_id, $biometric_session_id);
            $biometricAuthSession->biometric_authenticated_session_id = $this->database->lastInsertId();

            return $biometricAuthSession;
        }

        return false;
    }
}