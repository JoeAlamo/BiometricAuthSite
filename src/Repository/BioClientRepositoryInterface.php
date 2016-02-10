<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 17:32
 */

namespace BiometricSite\Repository;


interface BioClientRepositoryInterface {
    public function findByClientId($clientId);
} 