<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 17:24
 */

namespace BiometricSite\Service;

use BiometricSite\Controller\BioAuthV2ControllerInterface;

interface BioAuthV2ServiceInterface {
    public function performStage1($ip_address, BioAuthV2ControllerInterface $endpoint);

    public function performStage2($session_id, $client_id, $client_random, $client_mac, $ip_address, BioAuthV2ControllerInterface $endpoint);
} 