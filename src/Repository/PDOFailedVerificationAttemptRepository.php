<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 17:33
 */

namespace BiometricSite\Repository;


class PDOFailedVerificationAttemptRepository implements FailedVerificationAttemptRepositoryInterface {
    private $database;

    public function __construct(\PDO $database) {
        $this->database = $database;
    }

    public function add($biometric_client_id, $ip_address, $report_id) {
        $stmt = $this->database->prepare('
            INSERT INTO failed_verification_attempt
            (biometric_client_id, ip_address, report_id, occurred_at)
             VALUES (:biometric_client_id, :ip_address, :report_id, NOW())
        ');
        $stmt->bindParam('biometric_client_id', $biometric_client_id);
        $stmt->bindParam('ip_address', $ip_address);
        $stmt->bindParam('report_id', $report_id);

        return $stmt->execute();
    }

    public function reportIdAlreadyUsed($report_id) {
        $stmt = $this->database->prepare('
            SELECT count(failed_verification_attempt_id)
            FROM failed_verification_attempt
            WHERE report_id = :report_id
        ');
        $stmt->bindParam('report_id', $report_id);
        $stmt->execute();

        return $stmt->fetchColumn() > 0;
    }
}