<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 03/04/2016
 * Time: 15:48
 */

namespace BiometricSite\Service;

use BiometricSite\Model\BiometricClient;
use BiometricSite\Model\BiometricSession;
use BiometricSite\Repository\BioAuthSessionRepositoryInterface;
use BiometricSite\Repository\BioClientRepositoryInterface;
use BiometricSite\Repository\BioSessionRepositoryInterface;

abstract class AbstractBioAuthService {
    const BIO_AUTH_EXPIRY_TIME = 30;
    const SERVER_ID = "BFYZ5cGtO9SsqEzuUrWu7g~~";

    protected $bioClientRepository;
    protected $bioSessionRepository;
    protected $bioAuthSessionRepository;

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
     * @return bool
     */
    protected function verifyClientIdNotMalformed($client_id) {
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
    protected function verifyClientIdBelongsToValidClient($client_id) {
        return $this->bioClientRepository->findByClientId($client_id);
    }

    /**
     * @desc Wrapper for checking malformed session_id then valid session_id
     * @param $session_id
     * @return \BiometricSite\Model\BiometricSession|bool|false
     */
    protected function verifySessionId($session_id) {
        if (!$this->verifySessionIdNotMalformed($session_id)) {
            return false;
        }

        return $this->verifySessionIdBelongsToValidSession($session_id);
    }

    /**
     * @param $session_id
     * @return bool
     */
    protected function verifySessionIdNotMalformed($session_id) {
        if (!$session_id) {
            return false;
        }

        if (strlen($this->base64_url_decode($session_id)) !== 16) {
            return false;
        }

        return true;
    }

    /**
     * @param $session_id
     * @return \BiometricSite\Model\BiometricSession|false
     */
    protected function verifySessionIdBelongsToValidSession($session_id) {
        return $this->bioSessionRepository->findBySessionId($session_id);
    }

    /**
     * @return string
     */
    protected function generateUnusedSessionId() {
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

    protected function verifyClientMAC(BiometricClient $bioClient, BiometricSession $bioSession, $client_random, $client_mac) {
        // Calculate our own client_mac using clients authentication key
        $rawProvidedMAC = $this->base64_url_decode($client_mac);
        $rawAuthKey = $this->base64_url_decode($bioClient->authentication_key);
        // client_mac is hmac of client_id||server_id||session_id||client_random
        $rawClientId = $this->base64_url_decode($bioClient->client_id);
        $rawServerId = $this->base64_url_decode(self::SERVER_ID);
        $rawSessionId = $this->base64_url_decode($bioSession->session_id);
        $rawClientRandom = $this->base64_url_decode($client_random);
        $rawBaseClientMAC = $rawClientId . $rawServerId . $rawSessionId . $rawClientRandom;

        $calculatedMAC = substr(hash_hmac('sha256', $rawBaseClientMAC, $rawAuthKey, true), 0, 16);

        $this->logClientMACForDemo($bioSession->biometric_session_id, $rawProvidedMAC, $rawAuthKey, $rawClientId, $rawServerId, $rawSessionId, $rawClientRandom, $calculatedMAC);

        return $this->cryptoSecureCompare($calculatedMAC, $rawProvidedMAC);
    }

    private function logClientMACForDemo($bioSessionId, $providedMAC, $authKey, $clientId, $serverId, $sessionId, $clientRandom, $calculatedMAC) {
        $this->logToFile($bioSessionId, "== STAGE 2 REQUEST CLIENT MAC VERIFICATION ==", '------------------------------------------------');
        $this->logToFile($bioSessionId, "Provided client_mac:", $this->byteStringToHexArray($providedMAC));
        $this->logToFile($bioSessionId, "Auth key:", $this->byteStringToHexArray($authKey));
        $this->logToFile($bioSessionId, "client_id", $this->byteStringToHexArray($clientId));
        $this->logToFile($bioSessionId, "server_id:", $this->byteStringToHexArray($serverId));
        $this->logToFile($bioSessionId, "session_id:", $this->byteStringToHexArray($sessionId));
        $this->logToFile($bioSessionId, "client_random:", $this->byteStringToHexArray($clientRandom));
        $this->logToFile($bioSessionId, "Calculated client_mac:", $this->byteStringToHexArray($calculatedMAC));
    }

    protected function calculateServerMAC(BiometricSession $bioSession, BiometricClient $bioClient, $client_random) {
        $rawAuthKey = $this->base64_url_decode($bioClient->authentication_key);
        $rawServerId = $this->base64_url_decode(self::SERVER_ID);
        $rawClientRandom = $this->base64_url_decode($client_random);
        $rawServerMACBase = $rawServerId . $rawClientRandom;

        $rawServerMAC = substr(hash_hmac('sha256', $rawServerMACBase, $rawAuthKey, true), 0, 16);

        $this->logServerMACForDemo($bioSession->biometric_session_id, $rawServerMAC);

        return $this->base64_url_encode($rawServerMAC);
    }

    private function logServerMACForDemo($bioSessionId, $serverMAC) {
        $this->logToFile($bioSessionId, "server_mac:", $this->byteStringToHexArray($serverMAC));
    }

    /**
     * @desc URL safe b64 encode
     * @param $input
     * @return string
     */
    protected function base64_url_encode($input) {
        return strtr(base64_encode($input), '+/=', '-_~');
    }

    /**
     * @desc URL safe b64 decode
     * @param $input
     * @return string
     */
    protected function base64_url_decode($input) {
        return base64_decode(strtr($input, '-_~', '+/='));
    }

    protected function cryptoSecureCompare($stringA, $stringB) {
        if (!is_string($stringA) || !is_string($stringB)) {
            return false;
        }

        $stringLen = strlen($stringA);
        if ($stringLen !== strlen($stringB)) {
            return false;
        }

        $differences = 0;
        for ($i = 0; $i < $stringLen; $i++) {
            // XOR byte by byte, if different increment $differences
            $differences |= ord($stringA[$i]) ^ ord($stringB[$i]);
        }

        return $differences === 0;
    }

    protected function logToFile($biometric_session_id, $label, $text) {
        $filename = __DIR__ . "/../../public/" . "$biometric_session_id" . ".txt";
        file_put_contents($filename, $label . "\n", FILE_APPEND);
        file_put_contents($filename, $text . "\n", FILE_APPEND);
    }

    /**
     * @desc Convert raw byte string to C style octet array string
     * @param $byteString
     * @return string
     */
    protected function byteStringToHexArray($byteString) {
        if (!is_string($byteString)) {
            return '';
        }

        $stringLen = strlen($byteString);
        $hexString = bin2hex($byteString);
        $hexArrayString = "";
        $hexArray = str_split($hexString, 2);

        foreach ($hexArray as $key => $octet) {
            if ($key % 8 === 0) {
                $hexArrayString .= "\n";
            }
            $hexArrayString .= "0x";
            $hexArrayString .= strtoupper($octet);
            if ($key + 1 !== $stringLen) {
                $hexArrayString .= ", ";
            } else {
                $hexArrayString .= "\n\n";
            }
        }

        return $hexArrayString;
    }
} 
