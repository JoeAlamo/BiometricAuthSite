<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 17:25
 */

namespace BiometricSite\Service;


use BiometricSite\Controller\BioAuthV2ControllerInterface;
use BiometricSite\Repository\BioAuthSessionRepositoryInterface;
use BiometricSite\Repository\BioClientRepositoryInterface;
use BiometricSite\Repository\BioSessionRepositoryInterface;

class BioAuthV2Service implements BioAuthV2ServiceInterface {
    const BIO_AUTH_EXPIRY_TIME = 30;
    const SERVER_ID = "BFYZ5cGtO9SsqEzuUrWu7g~~";

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
     * @param $ip_address
     * @param BioAuthV2ControllerInterface $endpoint
     * @return BioAuthV2ControllerInterface
     */
    public function performStage1($ip_address, BioAuthV2ControllerInterface $endpoint)
    {
        // Randomly generate unique session_id
        $session_id = $this->generateUnusedSessionId();
        // Save biometric_session
        $bioSession = $this->bioSessionRepository->add($session_id, null, $ip_address, null);
        // Instruct controller to respond
        return $endpoint->stage1SuccessResponse($session_id, self::SERVER_ID);
    }

    public function performStage2(
        $session_id,
        $client_id,
        $client_random,
        $client_mac,
        $ip_address,
        BioAuthV2ControllerInterface $endpoint
    ) {
        // TODO: Implement performStage2() method.
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
