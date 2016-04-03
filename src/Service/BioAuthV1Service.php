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

    /**
     * @param $client_id
     * @return bool
     */
    private function verifyClientIdNotMalformed($client_id) {
        if (!$client_id) {
            return false;
        }

        if (strlen($this->base64_url_decode($client_id)) !== 16) {
            return false;
        }

        return true;
    }

    /**
     * @param $client_id
     * @return \BiometricSite\Model\BiometricClient|false
     */
    private function verifyClientIdBelongsToValidClient($client_id) {
        return $this->bioClientRepository->findByClientId($client_id);
    }

    /**
     * @return string
     */
    private function generateUnusedSessionId() {
        do {
            // Generate session_id securely
            $cryptographicallySecure = false;
            $session_id = $this->base64_url_encode(openssl_random_pseudo_bytes(16, $cryptographicallySecure));
            if ($cryptographicallySecure === false) {
                throw new \UnexpectedValueException("System is not cryptographically secure", 500);
            }
            // See if session_id has already been used for a session
            $session_idAlreadyExists = $this->bioSessionRepository->findBySessionId($session_id);
        } while ($session_idAlreadyExists !== false);

        return $session_id;
    }

    /**
     * @desc URL safe b64 encode
     * @param $input
     * @return string
     */
    private function base64_url_encode($input) {
        return strtr(base64_encode($input), '+/=', '-_~');
    }

    /**
     * @desc URL safe b64 decode
     * @param $input
     * @return string
     */
    private function base64_url_decode($input) {
        return base64_decode(strtr($input, '-_~', '+/='));
    }
}