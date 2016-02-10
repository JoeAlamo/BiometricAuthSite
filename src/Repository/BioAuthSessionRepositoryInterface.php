<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 19:00
 */

namespace BiometricSite\Repository;


interface BioAuthSessionRepositoryInterface {
    /**
     * @param $biometric_client_id
     * @param $biometric_session_id
     * @param int $duration
     * @return \BiometricSite\Model\BiometricAuthenticatedSession|false
     */
    public function add($biometric_client_id, $biometric_session_id, $duration = 30);
} 