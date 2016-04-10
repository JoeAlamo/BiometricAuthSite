<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 10/04/2016
 * Time: 14:55
 */

namespace BiometricSite\Controller;


interface FailedVerificationControllerInterface {
    public function logFailedVerificationAction();

    public function successfullyLoggedResponse();

    public function invalidClientIdResponse();
} 