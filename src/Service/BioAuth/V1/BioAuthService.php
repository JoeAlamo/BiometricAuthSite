<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 17:25
 */

namespace BiometricSite\Service\BioAuth\V1;


use BiometricSite\Repository\BioAuthSessionRepositoryInterface;
use BiometricSite\Repository\BioClientRepositoryInterface;
use BiometricSite\Repository\BioSessionRepositoryInterface;

class BioAuthService implements BioAuthServiceInterface {
    private $bioClientRepository;
    private $bioSessionRepository;
    private $bioAuthSessionRepository;

    public function __construct(
        BioClientRepositoryInterface $bioClientRepository,
        BioSessionRepositoryInterface $bioSessionRepository,
        BioAuthSessionRepositoryInterface $bioAuthSessionRepository
    ) {
        $this->bioClientRepository = $bioClientRepository;
        $this->bioSessionRepository = $bioSessionRepository;
        $this->bioAuthSessionRepository = $bioAuthSessionRepository;
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
        $biometricSession = $this->bioSessionRepository->add(openssl_random_pseudo_bytes(24), null, null);
        $biometricAuthenticatedSession = $this->bioAuthSessionRepository->add($client->biometric_client_id, $biometricSession->biometric_session_id, 30);

        return $endpoint->bioAuthSuccessful($biometricAuthenticatedSession->expires);
    }
}