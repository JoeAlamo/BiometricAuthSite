<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 17:32
 */

namespace BiometricSite\Repository;


interface PrevClientRandomRepositoryInterface {

    /**
     * @param $biometric_client_id
     * @param $client_random
     * @return bool
     */
    public function add($biometric_client_id, $client_random);

    /**
     * @param int $biometric_client_id
     * @param string $client_random
     * @return bool
     */
    public function hasBeenUsedPreviously($biometric_client_id, $client_random);
} 