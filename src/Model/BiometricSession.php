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
} 