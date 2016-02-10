<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 17:33
 */

namespace BiometricSite\Repository;


class PDOBioClientRepository implements BioClientRepositoryInterface {
    private $database;

    public function __construct(\PDO $database) {
        $this->database = $database;
    }

    public function findByClientId($client_id)
    {
        $stmt = $this->database->prepare('
            SELECT *
            FROM biometric_client
            WHERE client_id = :client_id
        ');
        $stmt->bindParam(':client_id', $client_id);
        $stmt->execute();

        $stmt->setFetchMode(\PDO::FETCH_CLASS, 'BiometricSite\\Model\\BiometricClient');

        return $stmt->fetch();
    }
}