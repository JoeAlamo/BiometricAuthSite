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

        // Verify timestamp
        if (!$this->verifyTimestamp($bioClient->biometric_client_id, $timestamp)) {
            return $endpoint->invalidTimestampResponse();
        }

        // Verify the tag and decrypt the ciphertext
        $rawSessionKey = $this->generateSessionKey($bioClient->key_derivation_key, $timestamp, $bioSession->session_id, $client_id, self::SERVER_ID);
        $plaintext =  $this->verifyAndDecryptCiphertext($ciphertext, $tag, $bioSession, $rawSessionKey);
        if ($plaintext === false) {
            $this->prevClientTimestampRepository->addOrUpdate($bioClient->biometric_client_id, $timestamp);
            return $endpoint->invalidTagResponse();
        }

        list($client_random, $client_mac) = $plaintext;
        // Verify client_random has not been used before by that client
        if ($this->prevClientRandomRepository->hasBeenUsedPreviously($bioClient->biometric_client_id, $client_random)) {
            $this->saveStage2SessionState($bioSession, $bioClient, $client_random, $ip_address, $timestamp);
            return $endpoint->invalidClientRandomResponse();
        }
        // Compute client_mac and verify provided is correct
        if (!$this->verifyClientMAC($bioClient, $bioSession, $client_random, $client_mac)) {
            $this->saveStage2SessionState($bioSession, $bioClient, $client_random, $ip_address, $timestamp);
            return $endpoint->invalidClientMACResponse();
        }

        // Calculate server_mac (server_id||client_random)
        $server_mac = $this->calculateServerMAC($bioSession, $bioClient, $client_random);
        // Create biometric authenticated session & save biometric_session state
        $bioAuthSession = $this->bioAuthSessionRepository->add($bioClient->biometric_client_id, $bioSession->biometric_session_id, self::BIO_AUTH_EXPIRY_TIME);
        $this->saveStage2SessionState($bioSession, $bioClient, $client_random, $ip_address, $timestamp);
        // Encrypt and tag
        list($responseCiphertext, $responseTag) = $this->encryptAndTag($server_mac, self::BIO_AUTH_EXPIRY_TIME, $bioSession, $rawSessionKey);

        return $endpoint->stage2SuccessResponse($responseCiphertext, $responseTag);
    }

    /**
     * @param $biometric_client_id
     * @param $timestamp
     * @return bool
     */
    private function verifyTimestamp($biometric_client_id, $timestamp) {
        // Verify timestamp isn't empty or null
        if (!$timestamp) {
            return false;
        }
        // Retrieve previous timestamp if it exists
        $prevTimestamp = $this->prevClientTimestampRepository->find($biometric_client_id, $timestamp);
        // If it exists, verify that it is greater than the previous timestamp
        if ($prevTimestamp !== false && $timestamp <= $prevTimestamp) {
            return false;
        }

        // Verify it is within 10 minutes of the current time
        return $this->prevClientTimestampRepository->isFresh($timestamp);
    }

    /**
     * @param $kdk
     * @param $timestamp
     * @param $session_id
     * @param $client_id
     * @param $server_id
     * @return string
     */
    private function generateSessionKey($kdk, $timestamp, $session_id, $client_id, $server_id) {
        $rawKDK = $this->base64_url_decode($kdk);
        $rawSessionId = $this->base64_url_decode($session_id);
        $rawClientId = $this->base64_url_decode($client_id);
        $rawServerId = $this->base64_url_decode($server_id);

        // Step 1: Extract PRK using KDK as IKM and timestamp||session_id as salt
        $prk = hash_hmac('sha256', $rawKDK, $timestamp . $rawSessionId, true);

        // Step 2: Expand PRK using client_id||server_id as context, and PRK as the key.
        // As we only need a 32byte session key, we only have to perform this once
        return hash_hmac('sha256', $rawClientId . $rawServerId . chr(1), $prk, true);
    }

    /**
     * @param $ciphertext
     * @param $tag
     * @param BiometricSession $bioSession
     * @param $sessionKey
     * @return array|bool
     */
    private function verifyAndDecryptCiphertext($ciphertext, $tag, BiometricSession $bioSession, $sessionKey) {
        $rawCiphertext = $this->base64_url_decode($ciphertext);
        $rawTag = $this->base64_url_decode($tag);
        $rawSessionId = $this->base64_url_decode($bioSession->session_id);

        // Verify and decrypt ciphertext
        $plaintextJSON = \Sodium\crypto_aead_chacha20poly1305_decrypt($rawCiphertext . $rawTag, $rawSessionId, '00000000', $sessionKey);

        if ($plaintextJSON === false) {
            return false;
        }

        // Attempt to decode JSON string
        $plaintext = json_decode($plaintextJSON);
        if (!array_key_exists('client_random', $plaintext) || !array_key_exists('client_mac', $plaintext)) {
            return false;
        }

        $this->logReceivedCiphertextForDemo($bioSession->biometric_session_id, $rawCiphertext, $rawTag, $sessionKey, $plaintextJSON);

        return [
            $plaintext['client_random'],
            $plaintext['client_mac']
        ];
    }

    private function logReceivedCiphertextForDemo($bioSessionId, $rawCiphertext, $rawTag, $rawSessionKey, $plaintext) {
        $this->logToFile($bioSessionId, "ciphertext:", $this->byteStringToHexArray($rawCiphertext));
        $this->logToFile($bioSessionId, "tag:", $this->byteStringToHexArray($rawTag));
        $this->logToFile($bioSessionId, "session key:", $this->byteStringToHexArray($rawSessionKey));
        $this->logToFile($bioSessionId, "plaintext:", $plaintext);
    }

    /**
     * @param $server_mac
     * @param $expires
     * @param BiometricSession $bioSession
     * @param $rawSessionKey
     * @return array
     */
    private function encryptAndTag($server_mac, $expires, BiometricSession $bioSession, $rawSessionKey) {
        $rawSessionId = $this->base64_url_decode($bioSession->session_id);
        $contents = [
            'server_mac' => $server_mac,
            'expires' => (int)$expires
        ];
        $json = json_encode((object)$contents);
        $jsonLen = strlen($json);

        $ciphertextAndTag = \Sodium\crypto_aead_chacha20poly1305_encrypt($json, $rawSessionId, '00000001', $rawSessionKey);

        $ciphertext = substr($ciphertextAndTag, 0, $jsonLen);
        $tag = substr($ciphertextAndTag, $jsonLen, 16);

        $this->logCreatedCiphertextForDemo($bioSession->biometric_session_id, $ciphertext, $tag, $rawSessionKey, $json);

        return [
            $this->base64_url_encode($ciphertext),
            $this->base64_url_encode($tag)
        ];
    }

    private function logCreatedCiphertextForDemo($bioSessionId, $rawCiphertext, $rawTag, $rawSessionKey, $plaintext) {
        $this->logToFile($bioSessionId, "response ciphertext:", $this->byteStringToHexArray($rawCiphertext));
        $this->logToFile($bioSessionId, "response tag:", $this->byteStringToHexArray($rawTag));
        $this->logToFile($bioSessionId, "session key:", $this->byteStringToHexArray($rawSessionKey));
        $this->logToFile($bioSessionId, "plaintext:", $plaintext);
    }

    /**
     * @param BiometricSession $bioSession
     * @param BiometricClient $bioClient
     * @param $client_random
     * @param $ip_address
     * @param $timestamp
     */
    private function saveStage2SessionState(BiometricSession $bioSession, BiometricClient $bioClient, $client_random, $ip_address, $timestamp) {
        // Save client_random to nonce cache
        $this->prevClientRandomRepository->add($bioClient->biometric_client_id, $client_random);
        // Save timestamp to nonce cache
        $this->prevClientTimestampRepository->addOrUpdate($bioClient->biometric_client_id, $timestamp);
        // Save biometric_session info
        $this->bioSessionRepository->update($bioSession->biometric_session_id, $client_random, $ip_address, $timestamp);
        // Associate to the biometric client
        $this->bioSessionRepository->associateSessionToClient($bioSession->biometric_session_id, $bioClient->biometric_client_id);
    }

}
