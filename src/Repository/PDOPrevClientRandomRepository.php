<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 17:33
 */

namespace BiometricSite\Repository;


class PDOPrevClientRandomRepository implements PrevClientRandomRepositoryInterface {
    private $database;

    public function __construct(\PDO $database) {
        $this->database = $database;
    }

    public function add($biometric_client_id, $client_random) {
        $stmt = $this->database->prepare('
            INSERT INTO previous_client_random
            (biometric_client_id, client_random) VALUES (:biometric_client_id, :client_random)
        ');
        $stmt->bindParam('biometric_client_id', $biometric_client_id);
        $stmt->bindParam('client_random', $client_random);

        return $stmt->execute();
    }

    public function hasBeenUsedPreviously($biometric_client_id, $client_random) {
        $stmt = $this->database->prepare('
            SELECT count(*) FROM previous_client_random
            WHERE biometric_client_id = :biometric_client_id
            AND client_random = :client_random
        ');
        $stmt->bindParam(':biometric_client_id', $biometric_client_id);
        $stmt->bindParam(':client_random', $client_random);
        $stmt->execute();

        $count = $stmt->fetchColumn();

        return $count > 0;
    }
}