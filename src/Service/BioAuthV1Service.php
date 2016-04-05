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

class BioAuthV1Service extends AbstractBioAuthService implements BioAuthV1ServiceInterface {

    public function __construct(
        BioClientRepositoryInterface $bioClientRepository,
        BioSessionRepositoryInterface $bioSessionRepository,
        BioAuthSessionRepositoryInterface $bioAuthSessionRepository
    ) {
        parent::__construct($bioClientRepository, $bioSessionRepository, $bioAuthSessionRepository);
    }

    /**
     * @param $client_id
     * @param $ip_address
     * @param BioAuthV1ControllerInterface $endpoint
     * @return BioAuthV1ControllerInterface
     */
    public function performStage1($client_id, $ip_address, BioAuthV1ControllerInterface $endpoint)
    {
        if (!$this->verifyClientIdNotMalformed($client_id)) {
            return $endpoint->invalidRequestResponse();
        }

        $bioClient = $this->verifyClientIdBelongsToValidClient($client_id);
        if (!$bioClient) {
            return $endpoint->invalidClientIdResponse();
        }
        // Generate session_id that hasn't been used before
        $session_id = $this->generateUnusedSessionId();
        // Create biometric authenticated session
        $bioSession = $this->bioSessionRepository->add($session_id, null, $ip_address, null);
        $this->bioSessionRepository->associateSessionToClient($bioSession->biometric_session_id, $bioClient->biometric_client_id);
        $bioAuthSession = $this->bioAuthSessionRepository->add($bioClient->biometric_client_id, $bioSession->biometric_session_id, self::BIO_AUTH_EXPIRY_TIME);

        return $endpoint->successfulResponse($bioAuthSession->expires);
    }
}