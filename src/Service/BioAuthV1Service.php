<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 17:25
 */

namespace BiometricSite\Service;


use BiometricSite\Controller\BioAuthV1ControllerInterface;
use BiometricSite\Repository\BioAuthSessionRepositoryInterface;
use BiometricSite\Repository\BioClientRepositoryInterface;
use BiometricSite\Repository\BioSessionRepositoryInterface;

class BioAuthV1Service implements BioAuthV1ServiceInterface {
    const BIO_AUTH_EXPIRY_TIME = 30;

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

    public function authenticate($client_id, $ip_address, BioAuthV1ControllerInterface $endpoint)
    {
        // Verify that client_id is not null
        if (!$client_id) {
            return $endpoint->invalidRequestResponse();
        }
        // Verify that client_id is a valid client
        $bioClient = $this->bioClientRepository->findByClientId($client_id);
        if (!$bioClient) {
            return $endpoint->invalidClientIDResponse();
        }
        // Generate session_id that hasn't been used before
        do {
            $session_id = base64_encode(openssl_random_pseudo_bytes(16));
            $session_idNotPreviouslyUsed = $this->bioSessionRepository->findBySessionId($session_id);
        } while ($session_idNotPreviouslyUsed === true);
        // Create biometric authenticated session
        $bioSession = $this->bioSessionRepository->add($session_id, null, $ip_address, null);
        $this->bioSessionRepository->associateSessionToClient($bioSession->biometric_session_id, $bioClient->biometric_client_id);
        $bioAuthSession = $this->bioAuthSessionRepository->add($bioClient->biometric_client_id, $bioSession->biometric_session_id, self::BIO_AUTH_EXPIRY_TIME);

        return $endpoint->successfulResponse($bioAuthSession->expires);
    }
}