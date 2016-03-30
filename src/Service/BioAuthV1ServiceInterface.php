<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 17:24
 */

namespace BiometricSite\Service;

use BiometricSite\Controller\BioAuthV1ControllerInterface;

interface BioAuthV1ServiceInterface {
    public function performStage1($client_id, $ip_address, BioAuthV1ControllerInterface $endpoint);
} 