<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 09/02/2016
 * Time: 12:43
 */

namespace BiometricSite\Model;


class BiometricSession {
    public $biometric_session_id;
    public $session_id;
    public $client_random;
    public $ip_address;
    public $timestamp;
    public $biometric_client_id;

    public function __construct($biometric_session_id, $session_id, $client_random, $ip_address, $timestamp, $biometric_client_id) {
        $this->biometric_session_id = $biometric_session_id;
        $this->session_id = $session_id;
        $this->client_random = $client_random;
        $this->ip_address = $ip_address;
        $this->timestamp = $timestamp;
        $this->biometric_client_id = $biometric_client_id;
    }
} 