<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 17:32
 */

namespace BiometricSite\Repository;


interface FailedVerificationAttemptRepositoryInterface {

    /**
     * @param $biometric_client_id
     * @param $ip_address
     * @param $report_id
     * @return bool
     */
    public function add($biometric_client_id, $ip_address, $report_id);

    /**
     * @param $report_id
     * @return bool
     */
    public function reportIdAlreadyUsed($report_id);

} 