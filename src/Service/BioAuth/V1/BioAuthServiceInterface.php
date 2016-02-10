<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 17:24
 */

namespace BiometricSite\Service\BioAuth\V1;

interface BioAuthServiceInterface {
    public function authenticate($client_id, $endpoint);
} 