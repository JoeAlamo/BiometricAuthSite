<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 29/03/2016
 * Time: 18:43
 */

namespace BiometricSite\Controller;


interface BioAuthV1ControllerInterface {
    public function stage1Action();

    public function invalidClientIdResponse();

    public function invalidRequestResponse();

    public function successfulResponse($duration);
} 