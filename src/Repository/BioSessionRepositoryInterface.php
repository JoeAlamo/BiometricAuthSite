<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 18:15
 */

namespace BiometricSite\Repository;


interface BioSessionRepositoryInterface {
    /**
     * @param $session_id
     * @param $client_random
     * @param $ip_address
     * @return \BiometricSite\Model\BiometricSession|false
     */
    public function add($session_id, $client_random, $ip_address, $timestamp);

    /**
     * @param $biometric_session_id
     * @param $biometric_client_id
     * @return bool
     */
    public function associateSessionToClient($biometric_session_id, $biometric_client_id);

    /**
     * @param $session_id
     * @return \BiometricSite\Model\BiometricSession|false
     */
    public function findBySessionId($session_id);
} 