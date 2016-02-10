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

    public function __construct($session_id, $client_random, $ip_address) {
        $this->session_id = $session_id;
        $this->client_random = $client_random;
        $this->ip_address = $ip_address;
    }
} 