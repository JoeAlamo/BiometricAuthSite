<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/04/2016
 * Time: 14:56
 */

namespace BiometricSite\Service;


use BiometricSite\Controller\FailedVerificationControllerInterface;

interface FailedVerificationServiceInterface {
    public function logFailedVerificationAttempt($client_id, $ip_address, FailedVerificationControllerInterface $endpoint);
} 