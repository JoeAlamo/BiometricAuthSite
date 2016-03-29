<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 17:24
 */

namespace BiometricSite\Service;

interface BioAuthV1ServiceInterface {
    public function authenticate($client_id, $ip_address, $endpoint);
} 