<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/02/2016
 * Time: 17:24
 */

namespace BiometricSite\Service;

use BiometricSite\Controller\BioAuthV3ControllerInterface;

interface BioAuthV3ServiceInterface {
    public function performStage1($ip_address, BioAuthV3ControllerInterface $endpoint);

    public function performStage2(
        $session_id,
        $client_id,
        $timestamp,
        $ciphertext,
        $tag,
        $ip_address,
        BioAuthV3ControllerInterface $endpoint
    );
} 