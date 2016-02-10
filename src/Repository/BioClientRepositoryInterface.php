<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 17:32
 */

namespace BiometricSite\Repository;


interface BioClientRepositoryInterface {
    /**
     * @param $clientId
     * @return \BiometricSite\Model\BiometricClient|false
     *
     */
    public function findByClientId($clientId);
} 