<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/04/2016
 * Time: 15:02
 */

namespace BiometricSite\Service;


use BiometricSite\Controller\FailedVerificationControllerInterface;
use BiometricSite\Repository\BioClientRepositoryInterface;
use BiometricSite\Repository\FailedVerificationAttemptRepositoryInterface;

class FailedVerificationService implements FailedVerificationServiceInterface {
    private $bioClientRepository;
    private $failedVerificationAttemptRepository;

    public function __construct(
        BioClientRepositoryInterface $bioClientRepository,
        FailedVerificationAttemptRepositoryInterface $failedVerificationAttemptRepository
    ) {
        $this->bioClientRepository = $bioClientRepository;
        $this->failedVerificationAttemptRepository = $failedVerificationAttemptRepository;
    }

    public function logFailedVerificationAttempt($client_id, $ip_address, FailedVerificationControllerInterface $endpoint)
    {
        // Verify client_id
        if (!$this->verifyClientIdNotMalformed($client_id)) {
            return $endpoint->invalidClientIdResponse();
        }
        $bioClient = $this->verifyClientIdBelongsToValidClient($client_id);
        if ($bioClient === false) {
            return $endpoint->invalidClientIdResponse();
        }
        // Generate report_id
        $report_id = $this->generateUnusedReportId();
        // Add to database
        $this->failedVerificationAttemptRepository->add($bioClient->biometric_client_id, $ip_address, $report_id);
        // Respond
        return $endpoint->successfullyLoggedResponse();
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
    private function generateUnusedReportId() {
        do {
            // Generate session_id securely
            $cryptographicallySecure = false;
            $report_id = $this->base64_url_encode(openssl_random_pseudo_bytes(16, $cryptographicallySecure));
            if ($cryptographicallySecure === false) {
                throw new \UnexpectedValueException("System is not cryptographically secure", 500);
            }
            // See if session_id has already been used for a session
            $report_idAlreadyUsed = $this->failedVerificationAttemptRepository->reportIdAlreadyUsed($report_id);
        } while ($report_idAlreadyUsed !== false);

        return $report_id;
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