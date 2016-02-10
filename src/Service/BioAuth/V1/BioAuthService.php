<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 17:25
 */

namespace BiometricSite\Service\BioAuth\V1;


use BiometricSite\Repository\BioClientRepositoryInterface;

class BioAuthService implements BioAuthServiceInterface {
    private $bioClientRepository;

    public function __construct(BioClientRepositoryInterface $bioClientRepository) {
        $this->bioClientRepository = $bioClientRepository;
    }

    public function authenticate($client_id, $endpoint)
    {
        // Verify that client_id is not null
        if (!$client_id) {
            return $endpoint->invalidRequest();
        }
        // Verify that client_id is a valid client
        $client = $this->bioClientRepository->findByClientId($client_id);
        if (!$client) {
            return $endpoint->unknownClientId();
        }
        // Create biometric authenticated session for the client

    }
}