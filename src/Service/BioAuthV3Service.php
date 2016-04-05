<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 17:25
 */

namespace BiometricSite\Service;


use BiometricSite\Controller\BioAuthV3ControllerInterface;
use BiometricSite\Model\BiometricClient;
use BiometricSite\Model\BiometricSession;
use BiometricSite\Repository\BioAuthSessionRepositoryInterface;
use BiometricSite\Repository\BioClientRepositoryInterface;
use BiometricSite\Repository\BioSessionRepositoryInterface;
use BiometricSite\Repository\PrevClientRandomRepositoryInterface;
use BiometricSite\Repository\PrevClientTimestampRepositoryInterface;

class BioAuthV3Service extends AbstractBioAuthService implements BioAuthV3ServiceInterface {
    private $prevClientRandomRepository;
    private $prevClientTimestampRepository;

    public function __construct(
        BioClientRepositoryInterface $bioClientRepository,
        BioSessionRepositoryInterface $bioSessionRepository,
        BioAuthSessionRepositoryInterface $bioAuthSessionRepository,
        PrevClientRandomRepositoryInterface $prevClientRandomRepository,
        PrevClientTimestampRepositoryInterface $prevClientTimestampRepository
    ) {
        parent::__construct($bioClientRepository, $bioSessionRepository, $bioAuthSessionRepository);
        $this->prevClientRandomRepository = $prevClientRandomRepository;
        $this->prevClientTimestampRepository = $prevClientTimestampRepository;
    }

    /**
     * @param $ip_address
     * @param BioAuthV3ControllerInterface $endpoint
     * @return BioAuthV3ControllerInterface
     */
    public function performStage1($ip_address, BioAuthV3ControllerInterface $endpoint)
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
        $timestamp,
        $ciphertext,
        $tag,
        $ip_address,
        BioAuthV3ControllerInterface $endpoint
    ) {
        // Verify session_id is linked to a valid session
        $bioSession = $this->verifySessionId($session_id);
        if (!$bioSession) {
            return $endpoint->invalidSessionIdResponse();
        }
        // Verify client_id
        if (!$this->verifyClientIdNotMalformed($client_id)) {
            return $endpoint->invalidRequestResponse();
        }

        $bioClient = $this->verifyClientIdBelongsToValidClient($client_id);
        if (!$bioClient) {
            return $endpoint->invalidClientIdResponse();
        }

    }

}
