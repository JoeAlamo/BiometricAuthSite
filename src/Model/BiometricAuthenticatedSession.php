<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 09/02/2016
 * Time: 12:43
 */

namespace BiometricSite\Model;


class BiometricAuthenticatedSession {
    public $biometric_authenticated_session_id;
    public $expires;
    public $biometric_client_id;
    public $biometric_session_id;

    public function __construct($expires, $biometric_client_id, $biometric_session_id) {
        $this->expires = $expires;
        $this->biometric_client_id = $biometric_client_id;
        $this->biometric_session_id = $biometric_session_id;
    }
} 